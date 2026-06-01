<?php

namespace App\Http\Resources\Api\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AccountProfileResource
 *
 * Reusable resource untuk data profil user (member & staff).
 * Aman dari N+1: semua relasi di-loadMissing sebelum resource dibuat.
 *
 * Expected loaded relations: roles, profileType, branch, staffLibrary
 */
class AccountProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user = $this->resource;

        // Primary role — role dengan sort_order tertinggi
        $primaryRole = $user->relationLoaded('roles')
            ? $user->roles->sortByDesc('sort_order')->first()
            : null;

        return [
            'id'               => $user->id,
            'fullname'         => $user->fullname,
            'email'            => $user->email,
            'phone'            => $user->phone,
            'avatar_url'       => $user->avatar_url,
            'member_code'      => $user->member_code,

            // Roles
            'roles' => $user->relationLoaded('roles')
                ? $user->roles->map(fn ($role) => [
                    'id'   => $role->id,
                    'code' => $role->code,
                    'name' => $role->name,
                ])->values()
                : [],

            'primary_role' => $primaryRole ? [
                'code' => $primaryRole->code,
                'name' => $primaryRole->name,
            ] : null,

            // Profile type (member only)
            'profile_type' => $user->relationLoaded('profileType') && $user->profileType
                ? [
                    'id'   => $user->profileType->id,
                    'code' => $user->profileType->code ?? $user->profileType->slug ?? null,
                    'name' => $user->profileType->name,
                    'slug' => $user->profileType->slug ?? null,
                ]
                : null,

            // Branch (jika terhubung ke cabang)
            'branch' => $user->relationLoaded('branch') && $user->branch
                ? [
                    'id'   => $user->branch->id,
                    'name' => $user->branch->name,
                    'code' => $user->branch->code ?? null,
                ]
                : null,

            // Staff library (jika staff terikat ke cabang)
            'staff_library' => $user->relationLoaded('staffLibrary') && $user->staffLibrary
                ? [
                    'id'   => $user->staffLibrary->id,
                    'name' => $user->staffLibrary->name,
                    'code' => $user->staffLibrary->code ?? null,
                ]
                : null,

            // Detail profil
            'institution_name' => $user->institution_name,
            'major_name'       => $user->major_name,
            'current_level'    => $user->current_level,
            'staff_position'   => $user->staff_position,

            // Status
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,

            // Timestamps
            'last_login_at' => $user->last_login_at?->toISOString(),
            'created_at'    => $user->created_at?->toISOString(),
            'updated_at'    => $user->updated_at?->toISOString(),
        ];
    }
}
