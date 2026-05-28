<?php

namespace App\Services\Admin;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\LibraryBranch;
use App\Models\Permission;
use App\Models\ProfileType;
use App\Models\Rack;
use App\Models\Role;
use App\Models\User;

class LookupService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function activeRoles()
    {
        return Role::where('is_active', true)->orderByDesc('sort_order')->get();
    }

    public function manageableRoles(?User $actor = null)
    {
        $query = Role::where('is_active', true);
        if ($actor && ! $actor->isSuperAdmin()) {
            $query->whereNotIn('code', [Role::SUPER_ADMIN]);
        }
        return $query->orderByDesc('sort_order')->get();
    }

    public function activeProfileTypes()
    {
        return ProfileType::where('is_active', true)->orderBy('sort_order')->get();
    }

    public function activeBranches(?User $actor = null)
    {
        return $this->branches->visibleBranches($actor ?: request()->user());
    }

    public function activeCategories()
    {
        return BookCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function activePermissions()
    {
        return Permission::where('is_active', true)->orderBy('module')->orderBy('name')->get();
    }

    public function activeBooks()
    {
        return Book::where('is_active', true)->orderBy('title')->get();
    }

    public function activeRacks(?User $actor = null, ?string $branchId = null)
    {
        $actor ??= request()->user();
        $branchId = $this->branches->normalizeBranchId($actor, $branchId);

        return Rack::query()
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('rack_code')
            ->get();
    }
}
