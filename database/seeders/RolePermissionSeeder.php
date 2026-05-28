<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $perm = fn (int $n) => sprintf('a7b2c3d4-000c-4000-8000-%012d', $n);

        $superAdmin = RoleSeeder::SUPER_ADMIN_ID;
        $adminBranch = RoleSeeder::ADMIN_BRANCH_ID;
        $staff = RoleSeeder::STAFF_ID;
        $member = RoleSeeder::MEMBER_ID;

        // Permission order follows PermissionSeeder.
        $memberPerms = [1, 5];
        $staffPerms = [1, 6, 7, 8, 11, 18, 19];
        $adminBranchPerms = [1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $superAdminPerms = range(1, 20);

        $assignments = [
            $superAdmin => $superAdminPerms,
            $adminBranch => $adminBranchPerms,
            $staff => $staffPerms,
            $member => $memberPerms,
        ];

        foreach ($assignments as $roleId => $permissionIds) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->insert([
                    'id' => Str::uuid()->toString(),
                    'role_id' => $roleId,
                    'permission_id' => $perm($permissionId),
                    'created_at' => $now,
                    'created_by' => null,
                ]);
            }
        }
    }
}
