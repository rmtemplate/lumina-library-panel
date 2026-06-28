<?php

namespace App\Services\Api\Account;

use App\Models\Borrowing;
use App\Models\Fine;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountService
{
    /**
     * Ambil data profil user dengan relasi yang diperlukan.
     * loadMissing agar tidak trigger query ulang jika sudah ter-load.
     */
    public function getProfile(User $user): User
    {
        $user->loadMissing(['roles', 'profileType', 'branch', 'staffLibrary']);

        return $this->attachComputedFields($user);
    }

    /**
     * Attaches presentation-only computed fields (borrowing stats,
     * membership status) as virtual attributes — never persisted, just
     * read by AccountProfileResource. Centralized here (not in the
     * Resource) so the counting logic has one home and isn't duplicated
     * if another endpoint ever needs the same numbers.
     */
    private function attachComputedFields(User $user): User
    {
        $user->setAttribute('stats', [
            'total_borrowed' => $user->borrowings()->count(),
            'active_borrowings' => $user->borrowings()
                ->whereIn('status', [Borrowing::ACTIVE, Borrowing::WAITING_PAYMENT])
                ->count(),
            'total_returned' => $user->borrowings()
                ->whereIn('status', [Borrowing::RETURNED, Borrowing::RETURNED_WITH_FINE])
                ->count(),
        ]);

        // A pickup_no_show fine blocks new checkouts at the user level (see
        // CheckoutService) even though the borrowing itself is terminal —
        // surfaced here so "Membership Status" reflects that restriction
        // instead of just echoing the raw is_active flag.
        $hasBlockingFine = Fine::query()
            ->where('user_id', $user->id)
            ->where('fine_type', Fine::TYPE_PICKUP_NO_SHOW)
            ->where('status', Fine::UNPAID)
            ->exists();

        $user->setAttribute('membership_status', match (true) {
            ! $user->is_active => 'inactive',
            $hasBlockingFine => 'restricted',
            default => 'active',
        });

        return $user;
    }

    /**
     * Update field profil yang diizinkan.
     *
     * Rules:
     * - Email tidak boleh diupdate dari sini.
     * - Role, branch, staff_library tidak boleh diupdate dari sini.
     * - staff_position hanya bisa diupdate oleh user dengan role staff.
     * - Member yang mengirim staff_position → diabaikan (tidak error).
     */
    public function updateProfile(User $user, array $payload): User
    {
        $user->loadMissing(['roles', 'profileType', 'branch', 'staffLibrary']);

        $allowedFields = [
            'fullname',
            'phone',
            'institution_name',
            'major_name',
            'current_level',
            'address',
            'date_of_birth',
            'gender',
        ];

        // staff_position hanya boleh diupdate oleh staff
        if ($user->isStaff() && array_key_exists('staff_position', $payload)) {
            $allowedFields[] = 'staff_position';
        }

        // Ambil hanya field yang ada di payload DAN ada di allowedFields —
        // email, member_code, role, branch tidak pernah bisa lolos dari sini
        // karena tidak pernah masuk allowedFields, terlepas dari apa yang
        // dikirim client.
        $updates = array_intersect_key($payload, array_flip($allowedFields));

        if (! empty($updates)) {
            $user->update($updates);
        }

        $user->refresh();

        return $this->attachComputedFields($user);
    }

    /**
     * Upload dan simpan avatar baru.
     * Hapus avatar lama jika local dan file ada.
     */
    public function updateAvatar(User $user, UploadedFile $file): User
    {
        $user->loadMissing(['roles', 'profileType', 'branch', 'staffLibrary']);

        // Hapus avatar lama jika bukan external
        $this->deleteOldAvatarFile($user);

        // Simpan file baru: avatars/{user_id}/{filename}
        $path = $file->store(
            path: 'avatars/' . $user->id,
            options: ['disk' => 'public']
        );

        $user->update([
            'avatar_path'        => $path,
            'avatar_is_external' => false,
        ]);

        $user->refresh();

        return $this->attachComputedFields($user);
    }

    /**
     * Hapus avatar user.
     * Set avatar_path dan avatar_is_external ke null/false.
     */
    public function deleteAvatar(User $user): User
    {
        $user->loadMissing(['roles', 'profileType', 'branch', 'staffLibrary']);

        $this->deleteOldAvatarFile($user);

        $user->update([
            'avatar_path'        => null,
            'avatar_is_external' => false,
        ]);

        $user->refresh();

        return $this->attachComputedFields($user);
    }

    /**
     * Update password user.
     *
     * Rules:
     * - Verifikasi current_password terhadap password_hash.
     * - Password baru tidak boleh sama dengan password lama.
     * - Update password_hash dengan Hash::make.
     * - Sesi yang sedang dipakai (current token) tetap aktif; sesi LAIN
     *   ikut di-revoke jika $logoutOtherSessions true — sama seperti
     *   ApiAuthenticationService::logoutAll(), tapi current token
     *   dikecualikan agar request ini sendiri tidak ikut ter-revoke.
     *
     * @throws ValidationException jika current_password salah atau password
     *                              baru sama dengan password lama
     */
    public function updatePassword(User $user, array $payload, bool $logoutOtherSessions = false): void
    {
        if (! Hash::check($payload['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        if (Hash::check($payload['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['Password baru tidak boleh sama dengan password lama.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($payload['password']),
        ]);

        if ($logoutOtherSessions) {
            $this->revokeOtherSessions($user);
        }
    }

    /**
     * Revokes every Sanctum token EXCEPT the one used for the current
     * request — same effect as ApiAuthenticationService::logoutAll() but
     * scoped to "other devices" so the session that just changed the
     * password isn't logged out by its own request.
     */
    private function revokeOtherSessions(User $user): void
    {
        $currentTokenId = $user->currentAccessToken()?->id;

        $tokensQuery = $user->tokens();
        if ($currentTokenId) {
            $tokensQuery->where('id', '!=', $currentTokenId);
        }

        $tokensQuery->delete();

        $sessionsQuery = UserSession::query()
            ->where('user_id', $user->id)
            ->where('is_active', true);

        if ($currentTokenId) {
            $sessionsQuery->where('personal_access_token_id', '!=', $currentTokenId);
        }

        $sessionsQuery->update([
            'is_active' => false,
            'last_activity_at' => now(),
        ]);
    }

    // ── Private Helpers ────────────────────────────────────────────────────

    /**
     * Hapus file avatar lama dari storage jika:
     * - avatar_path tidak null
     * - bukan external URL
     * - file ada di disk public
     */
    private function deleteOldAvatarFile(User $user): void
    {
        if (! $user->avatar_path) {
            return;
        }

        $isExternal = $user->avatar_is_external
            || str_starts_with($user->avatar_path, 'http://')
            || str_starts_with($user->avatar_path, 'https://');

        if ($isExternal) {
            return;
        }

        if (Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }
}
