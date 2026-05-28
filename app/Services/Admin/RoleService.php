<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function create(array $data, ?string $actorId = null): Role
    {
        return DB::transaction(function () use ($data, $actorId) {
            $permissionIds = collect($data['permission_ids'] ?? [])->filter()->unique()->values();
            unset($data['permission_ids']);
            $data['created_by'] = $actorId;

            $role = Role::create($data);
            $this->syncPermissions($role, $permissionIds->all(), $actorId);

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data, ?string $actorId = null): Role
    {
        return DB::transaction(function () use ($role, $data, $actorId) {
            $permissionIds = collect($data['permission_ids'] ?? [])->filter()->unique()->values();
            unset($data['permission_ids']);
            $data['updated_by'] = $actorId;

            $role->update($data);
            $this->syncPermissions($role, $permissionIds->all(), $actorId);

            return $role->fresh('permissions');
        });
    }

    public function delete(Role $role): void
    {
        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Role tidak dapat dihapus karena masih digunakan pengguna.',
            ]);
        }

        $role->delete();
    }

    private function syncPermissions(Role $role, array $permissionIds, ?string $actorId = null): void
    {
        RolePermission::where('role_id', $role->id)->delete();

        foreach ($permissionIds as $permissionId) {
            RolePermission::create([
                'id' => (string) Str::uuid(),
                'role_id' => $role->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'created_by' => $actorId,
            ]);
        }
    }
}
