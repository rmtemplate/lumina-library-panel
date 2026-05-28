<?php

namespace App\Models;

use App\Enums\UserRoleCode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN_BRANCH = 'admin_branch';
    public const STAFF = 'staff';
    public const MEMBER = 'member';

    // Backward-compatible aliases from older package.
    public const ADMIN = self::SUPER_ADMIN;
    public const LIBRARIAN = self::STAFF;
    public const MAHASISWA = self::MEMBER;

    protected $table = 'roles';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code', 'name', 'description', 'scope', 'is_admin_role', 'is_staff_role',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_admin_role' => 'boolean',
        'is_staff_role' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withPivot(['assigned_at', 'is_active'])
            ->wherePivot('is_active', true);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'role_id');
    }

    public function getIsSuperAdminAttribute(): bool
    {
        return $this->code === UserRoleCode::SUPER_ADMIN->value;
    }
}
