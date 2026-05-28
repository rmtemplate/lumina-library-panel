<?php

namespace App\Services\Admin;

use App\Models\LibraryBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BranchAccessService
{
    public function isSuperAdmin(?User $user): bool
    {
        return (bool) $user?->isSuperAdmin();
    }

    public function branchId(?User $user): ?string
    {
        return $user?->getBranchScopeId();
    }

    public function visibleBranches(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($user->isSuperAdmin()) {
            return LibraryBranch::where('is_active', true)->orderBy('name')->get();
        }

        return LibraryBranch::where('id', $this->branchId($user))->where('is_active', true)->get();
    }

    public function scope(Builder $query, ?User $user, string $column = 'branch_id'): Builder
    {
        if ($user && ! $user->isSuperAdmin()) {
            $query->where($column, $this->branchId($user));
        }

        return $query;
    }

    public function assertCanAccessBranch(?User $user, ?string $branchId): void
    {
        if (! $user || ! $user->canAccessAdminPanel()) {
            abort(403, 'Akses admin ditolak.');
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if (! $branchId || $branchId !== $this->branchId($user)) {
            abort(403, 'Anda tidak memiliki akses ke branch ini.');
        }
    }

    public function normalizeBranchId(?User $user, ?string $requestedBranchId = null): ?string
    {
        if ($user?->isSuperAdmin()) {
            return $requestedBranchId;
        }

        return $this->branchId($user);
    }

    public function requireBranchId(?User $user, ?string $requestedBranchId = null): string
    {
        $branchId = $this->normalizeBranchId($user, $requestedBranchId);

        if (! $branchId) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch wajib dipilih.',
            ]);
        }

        $this->assertCanAccessBranch($user, $branchId);

        return $branchId;
    }
}
