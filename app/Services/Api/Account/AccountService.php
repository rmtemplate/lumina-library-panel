<?php

namespace App\Services\Api\Account;

use App\Models\Role;
use App\Models\User;
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
        ];

        // staff_position hanya boleh diupdate oleh staff
        if ($user->isStaff() && array_key_exists('staff_position', $payload)) {
            $allowedFields[] = 'staff_position';
        }

        $updates = collect($payload)
            ->only($allowedFields)
            ->filter(fn ($value) => $value !== null || array_key_exists($allowedFields[array_search($value, $payload)], $payload))
            ->toArray();

        // Filter aman: ambil hanya field yang ada di payload dan ada di allowedFields
        $updates = array_intersect_key($payload, array_flip($allowedFields));

        if (! empty($updates)) {
            $user->update($updates);
        }

        $user->refresh();

        return $user;
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

        return $user;
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

        return $user;
    }

    /**
     * Update password user.
     *
     * Rules:
     * - Verifikasi current_password terhadap password_hash.
     * - Update password_hash dengan Hash::make.
     * - Tidak logout session yang ada.
     *
     * @throws ValidationException jika current_password salah
     */
    public function updatePassword(User $user, array $payload): void
    {
        // Verifikasi current password
        if (! Hash::check($payload['current_password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        // Update password_hash
        $user->update([
            'password_hash' => Hash::make($payload['password']),
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
