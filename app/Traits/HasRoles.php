<?php

namespace App\Traits;

use App\Models\Role;

trait HasRoles
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot(['assigned_at', 'is_active'])
            ->wherePivot('is_active', true);
    }

    public function hasRole(string $code): bool
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        return $roles->contains('code', $code);
    }

    public function hasAnyRole(array $codes): bool
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();
        return $roles->whereIn('code', $codes)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isBranchAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN_BRANCH);
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->is_active && $this->is_verified && $this->hasAnyRole([Role::SUPER_ADMIN, Role::ADMIN_BRANCH]);
    }

    public function isAdmin(): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function isStaff(): bool
    {
        return $this->hasRole(Role::STAFF);
    }

    public function isMember(): bool
    {
        return $this->hasRole(Role::MEMBER);
    }

    public function primaryRole(): ?string
    {
        return $this->roles->sortByDesc('sort_order')->first()?->code;
    }

    public function primaryRoleName(): ?string
    {
        return $this->roles->sortByDesc('sort_order')->first()?->name;
    }
}
