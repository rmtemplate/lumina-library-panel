<?php

namespace App\Services\Api\Staff\Member;

use App\Models\ProfileType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Api\Staff\Broadcast\StaffBroadcastService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Staff-initiated member registration — the walk-in onboarding flow used
 * when a Borrow Cart's member step can't find an existing account. Reuses
 * the exact same User-creation shape as
 * ApiAuthenticationService::register() (Hash::make, member_code uniqueness,
 * member Role assignment), but differs in two deliberate ways:
 *
 * 1. The Student ID (NIM) the staff types in becomes `member_code`
 *    directly — it's not synthesized, because for a student that NIM IS
 *    their real institutional identifier (unlike self-registration, which
 *    has no such input and must generate a synthetic MBR-... code).
 * 2. `is_verified` is set true immediately, with no email-verification
 *    token/mail step. The staff is physically verifying the member's
 *    identity in person right now — that IS the verification, so it would
 *    be redundant (and would block them from finishing the borrow
 *    workflow today) to also require an email link click.
 */
class StaffMemberService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly StaffBroadcastService $staffBroadcastService
    ) {
    }

    /**
     * @return array{user: User, generated_password: string}
     */
    public function register(User $staff, array $payload, ?UploadedFile $photo = null): array
    {
        return DB::transaction(function () use ($staff, $payload, $photo) {
            $branchId = $this->staffAccessService->branchId($staff);

            $memberRole = Role::query()->where('code', Role::MEMBER)->where('is_active', true)->first();
            if (! $memberRole) {
                throw ValidationException::withMessages([
                    'role' => ['Role member belum tersedia. Jalankan seeder role terlebih dahulu.'],
                ]);
            }

            $profileType = ProfileType::query()->where('code', ProfileType::MAHASISWA)->first();

            $generatedPassword = $this->generateTemporaryPassword();

            $user = User::query()->create([
                'id' => (string) Str::uuid(),
                'branch_id' => $branchId,
                'fullname' => $payload['fullname'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'password_hash' => Hash::make($generatedPassword),
                'member_code' => $payload['student_id'],
                'profile_type_id' => $profileType?->id,
                'institution_name' => $payload['faculty'],
                'major_name' => $payload['study_program'],
                'address' => $payload['address'] ?? null,
                'gender' => $payload['gender'],
                'is_verified' => true,
                'is_active' => true,
                'created_by' => $staff->id,
                'updated_by' => $staff->id,
            ]);

            if ($photo) {
                $path = $photo->store('avatars/' . $user->id, ['disk' => 'public']);
                $user->forceFill(['avatar_path' => $path, 'avatar_is_external' => false])->save();
            }

            UserRole::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'role_id' => $memberRole->id,
                'assigned_at' => now(),
                'assigned_by' => $staff->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->staffBroadcastService->memberRegistered($user, $branchId);

            return [
                'user' => $user->refresh(),
                'generated_password' => $generatedPassword,
            ];
        }, 3);
    }

    /**
     * GET /staff/members/search — Borrow Cart's manual member search
     * (name / email / phone / NIM).
     */
    public function search(User $staff, string $query): \Illuminate\Support\Collection
    {
        $this->staffAccessService->assertStaff($staff);

        $keyword = '%' . addcslashes(trim($query), '\\%_') . '%';

        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('code', Role::MEMBER))
            ->where(function ($q) use ($keyword) {
                $q->where('fullname', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword)
                    ->orWhere('phone', 'like', $keyword)
                    ->orWhere('member_code', 'like', $keyword);
            })
            ->limit(20)
            ->get();
    }

    /**
     * GET /staff/members/lookup — exact-match resolve, used when the
     * Borrow Cart's "scan member" step reads a member card QR/barcode
     * encoding their member_code directly.
     */
    public function lookup(User $staff, string $code): ?User
    {
        $this->staffAccessService->assertStaff($staff);

        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('code', Role::MEMBER))
            ->where(function ($q) use ($code) {
                $q->where('member_code', $code)->orWhere('id', $code);
            })
            ->first();
    }

    private function generateTemporaryPassword(): string
    {
        return 'luminalibrary' . now()->year . random_int(10, 99);
    }
}
