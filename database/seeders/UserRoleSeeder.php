<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $superUser = 'c1b2c3d4-0003-4000-8000-000000000006';

        $rSuper = RoleSeeder::SUPER_ADMIN_ID;
        $rAdminBranch = RoleSeeder::ADMIN_BRANCH_ID;
        $rStaff = RoleSeeder::STAFF_ID;
        $rMember = RoleSeeder::MEMBER_ID;

        $assignments = [
            // Members
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000001', 'role' => $rMember, 'by' => null],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000002', 'role' => $rMember, 'by' => null],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000003', 'role' => $rMember, 'by' => null],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000007', 'role' => $rMember, 'by' => null],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000008', 'role' => $rMember, 'by' => null],

            // Staff / petugas: prepared for future mobile/API, no admin web access.
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000004', 'role' => $rStaff, 'by' => $superUser],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000005', 'role' => $rStaff, 'by' => $superUser],

            // Admin Web access
            ['user' => $superUser, 'role' => $rSuper, 'by' => null],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000009', 'role' => $rAdminBranch, 'by' => $superUser],
            ['user' => 'c1b2c3d4-0003-4000-8000-000000000010', 'role' => $rAdminBranch, 'by' => $superUser],
        ];

        foreach ($assignments as $assignment) {
            DB::table('user_roles')->insert([
                'id' => Str::uuid()->toString(),
                'user_id' => $assignment['user'],
                'role_id' => $assignment['role'],
                'assigned_at' => $now,
                'assigned_by' => $assignment['by'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }
}
