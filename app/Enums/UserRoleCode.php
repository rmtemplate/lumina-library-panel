<?php

namespace App\Enums;

enum UserRoleCode: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN_BRANCH = 'admin_branch';
    case STAFF = 'staff';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN_BRANCH => 'Admin Branch',
            self::STAFF => 'Staff / Petugas',
            self::MEMBER => 'User / Member',
        };
    }
}
