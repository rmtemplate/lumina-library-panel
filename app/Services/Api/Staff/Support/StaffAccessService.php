<?php

namespace App\Services\Api\Staff\Support;

use App\Models\LibraryBranch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffAccessService
{
    public function assertStaff(User $user): User
    {
        $user->loadMissing(['roles', 'branch', 'staffLibrary']);

        $roleCodes = $user->roles
            ->pluck('code')
            ->filter()
            ->values();

        if (! $user->is_active || ! $user->is_verified) {
            throw ValidationException::withMessages([
                'auth' => ['Akun staff tidak aktif atau belum terverifikasi.'],
            ]);
        }

        if (! $roleCodes->contains(Role::STAFF)) {
            throw ValidationException::withMessages([
                'auth' => ['Endpoint ini hanya dapat diakses oleh staff.'],
            ]);
        }

        if ($roleCodes->contains(Role::SUPER_ADMIN) || $roleCodes->contains(Role::ADMIN_BRANCH)) {
            throw ValidationException::withMessages([
                'auth' => ['Akun admin tidak dapat memakai endpoint staff mobile.'],
            ]);
        }

        return $user;
    }

    public function branchId(User $staff): string
    {
        $this->assertStaff($staff);

        $branchId = $staff->staff_library_id ?: $staff->branch_id;

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch' => ['Staff belum terhubung ke perpustakaan/cabang.'],
            ]);
        }

        return (string) $branchId;
    }

    public function branch(User $staff): LibraryBranch
    {
        $branchId = $this->branchId($staff);

        $branch = LibraryBranch::query()
            ->where('id', $branchId)
            ->where('is_active', true)
            ->first();

        if (! $branch) {
            throw ValidationException::withMessages([
                'branch' => ['Perpustakaan/cabang staff tidak ditemukan atau tidak aktif.'],
            ]);
        }

        return $branch;
    }

    public function assertSameBranch(User $staff, ?string $branchId): void
    {
        $staffBranchId = $this->branchId($staff);

        if (! $branchId || (string) $branchId !== (string) $staffBranchId) {
            throw ValidationException::withMessages([
                'branch' => ['Staff tidak memiliki akses ke data cabang/perpustakaan ini.'],
            ]);
        }
    }
}
