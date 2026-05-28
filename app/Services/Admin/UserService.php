<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function paginate(array $filters = [], ?User $actor = null): LengthAwarePaginator
    {
        $query = User::with(['roles', 'profileType', 'branch']);
        $this->branches->scope($query, $actor, 'branch_id');

        return $query
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($sub) => $sub
                    ->where('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn ($query, string $roleId) => $query->whereHas('roles', fn ($role) => $role->where('roles.id', $roleId)))
            ->when($filters['branch_id'] ?? null, fn ($query, string $branchId) => $query->where('branch_id', $this->branches->normalizeBranchId($actor, $branchId)))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderByDesc('created_at')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function create(array $data, ?string $actorId = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actorId, $actor) {
            $roleIds = Arr::wrap($data['role_ids'] ?? []);
            $branchId = $this->branches->normalizeBranchId($actor, $data['branch_id'] ?? null);

            if ($this->roleIdsContainBranchScoped($roleIds) && ! $branchId) {
                throw ValidationException::withMessages(['branch_id' => 'Branch wajib dipilih untuk Admin Branch, Staff, dan Member.']);
            }

            if ($branchId) {
                $this->branches->assertCanAccessBranch($actor, $branchId);
            }

            $user = User::create([
                'branch_id' => $branchId,
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password_hash' => Hash::make($data['password']),
                'member_code' => $data['member_code'],
                'profile_type_id' => $data['profile_type_id'],
                'institution_name' => $data['institution_name'] ?? null,
                'major_name' => $data['major_name'] ?? null,
                'current_level' => $data['current_level'] ?? null,
                'staff_position' => $data['staff_position'] ?? null,
                'staff_library_id' => in_array($branchId, [null, ''], true) ? null : $branchId,
                'is_verified' => (bool) ($data['is_verified'] ?? true),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $actorId,
            ]);

            $this->syncRoles($user, $roleIds, $actorId, $actor);

            return $user->load(['roles', 'profileType', 'branch']);
        });
    }

    public function update(User $user, array $data, ?string $actorId = null, ?User $actor = null): User
    {
        $this->assertCanManageUser($user, $actor);

        return DB::transaction(function () use ($user, $data, $actorId, $actor) {
            $payload = Arr::only($data, [
                'fullname', 'email', 'phone', 'member_code', 'profile_type_id',
                'institution_name', 'major_name', 'current_level', 'staff_position',
                'is_verified', 'is_active',
            ]);

            if (array_key_exists('branch_id', $data)) {
                $payload['branch_id'] = $this->branches->normalizeBranchId($actor, $data['branch_id']);
                $payload['staff_library_id'] = $payload['branch_id'];
            }

            if (! empty($data['password'])) {
                $payload['password_hash'] = Hash::make($data['password']);
            }

            $payload['updated_by'] = $actorId;
            $user->update($payload);

            if (array_key_exists('role_ids', $data)) {
                $this->syncRoles($user, Arr::wrap($data['role_ids']), $actorId, $actor);
            }

            return $user->fresh(['roles', 'profileType', 'branch']);
        });
    }

    public function delete(User $user, ?string $actorId = null, ?User $actor = null): void
    {
        $this->assertCanManageUser($user, $actor);

        if ($actorId && $user->id === $actorId) {
            throw ValidationException::withMessages(['user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.']);
        }

        DB::transaction(function () use ($user, $actorId) {
            $user->update(['updated_by' => $actorId]);
            $user->delete();
        });
    }

    public function toggleStatus(User $user, ?string $actorId = null, ?User $actor = null): User
    {
        $this->assertCanManageUser($user, $actor);

        if ($actorId && $user->id === $actorId) {
            throw ValidationException::withMessages(['user' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.']);
        }

        $user->update(['is_active' => ! $user->is_active, 'updated_by' => $actorId]);
        return $user->fresh(['roles']);
    }

    public function syncRoles(User $user, array $roleIds, ?string $actorId = null, ?User $actor = null): void
    {
        $this->assertCanManageUser($user, $actor);

        $roleIds = collect($roleIds)->filter()->unique()->values();

        if ($roleIds->isEmpty()) {
            throw ValidationException::withMessages(['role_ids' => 'Pilih minimal satu role.']);
        }

        $roles = Role::whereIn('id', $roleIds)->get();
        if ($roles->count() !== $roleIds->count()) {
            throw ValidationException::withMessages(['role_ids' => 'Role yang dipilih tidak valid.']);
        }

        if ($actor && ! $actor->isSuperAdmin() && $roles->contains('code', Role::SUPER_ADMIN)) {
            throw ValidationException::withMessages(['role_ids' => 'Admin Branch tidak dapat memberikan role Super Admin.']);
        }

        UserRole::where('user_id', $user->id)->update(['is_active' => false]);

        foreach ($roleIds as $roleId) {
            $assignment = UserRole::withTrashed()->firstOrNew(['user_id' => $user->id, 'role_id' => $roleId]);
            if (! $assignment->exists) $assignment->assigned_at = now();
            $assignment->assigned_by = $actorId;
            $assignment->is_active = true;
            $assignment->deleted_at = null;
            $assignment->save();
        }
    }

    private function assertCanManageUser(User $target, ?User $actor): void
    {
        if (! $actor) abort(403);
        if ($actor->isSuperAdmin()) return;
        $this->branches->assertCanAccessBranch($actor, $target->branch_id);
        if ($target->isSuperAdmin()) abort(403, 'Admin Branch tidak dapat mengubah Super Admin.');
    }

    private function roleIdsContainBranchScoped(array $roleIds): bool
    {
        return Role::whereIn('id', $roleIds)->whereIn('code', [Role::ADMIN_BRANCH, Role::STAFF, Role::MEMBER])->exists();
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        return in_array($perPage, [10, 20, 30, 50, 100], true) ? $perPage : 20;
    }
}
