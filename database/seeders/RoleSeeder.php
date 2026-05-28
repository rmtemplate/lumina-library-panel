<?php

namespace Database\Seeders;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public const SUPER_ADMIN_ID = 'b1b2c3d4-0002-4000-8000-000000000001';
    public const ADMIN_BRANCH_ID = 'b1b2c3d4-0002-4000-8000-000000000002';
    public const STAFF_ID = 'b1b2c3d4-0002-4000-8000-000000000003';
    public const MEMBER_ID = 'b1b2c3d4-0002-4000-8000-000000000004';

    public function run(): void
    {
        $now = Carbon::now();

        DB::table('roles')->insert([
            [
                'id' => self::SUPER_ADMIN_ID,
                'code' => Role::SUPER_ADMIN,
                'name' => 'Super Admin',
                'description' => 'Akses tertinggi. Mengelola seluruh branch, admin branch, master global, permission, role, settings, dan laporan global.',
                'scope' => 'global',
                'is_admin_role' => true,
                'is_staff_role' => false,
                'sort_order' => 100,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => null,
                'updated_by' => null,
            ],
            [
                'id' => self::ADMIN_BRANCH_ID,
                'code' => Role::ADMIN_BRANCH,
                'name' => 'Admin Branch',
                'description' => 'Admin cabang/perpustakaan. Hanya mengelola data pada branch miliknya sendiri.',
                'scope' => 'branch',
                'is_admin_role' => true,
                'is_staff_role' => false,
                'sort_order' => 90,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => null,
                'updated_by' => null,
            ],
            [
                'id' => self::STAFF_ID,
                'code' => Role::STAFF,
                'name' => 'Staff / Petugas',
                'description' => 'Petugas operasional branch. Disiapkan untuk mobile/API; tidak dapat login ke admin panel web.',
                'scope' => 'branch',
                'is_admin_role' => false,
                'is_staff_role' => true,
                'sort_order' => 50,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => null,
                'updated_by' => null,
            ],
            [
                'id' => self::MEMBER_ID,
                'code' => Role::MEMBER,
                'name' => 'User / Member',
                'description' => 'Member perpustakaan. Disiapkan untuk mobile/API; tidak dapat login ke admin panel web.',
                'scope' => 'branch',
                'is_admin_role' => false,
                'is_staff_role' => false,
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => null,
                'updated_by' => null,
            ],
        ]);
    }
}
