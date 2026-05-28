<?php

namespace Database\Seeders;

use App\Models\ProfileType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $profileTypeId = ProfileType::query()->where('code', 'umum')->value('id')
            ?: ProfileType::query()->value('id');

        $superRole = Role::query()->updateOrCreate(
            ['code' => Role::SUPER_ADMIN],
            [
                'id' => RoleSeeder::SUPER_ADMIN_ID,
                'name' => 'Super Admin',
                'description' => 'Akses tertinggi Lumina Library.',
                'scope' => 'global',
                'is_admin_role' => true,
                'is_staff_role' => false,
                'sort_order' => 100,
                'is_active' => true,
            ]
        );

        $superAdmin = User::withTrashed()->updateOrCreate(
            ['email' => 'super@lumina.ac.id'],
            [
                'id' => 'c1b2c3d4-0003-4000-8000-000000000006',
                'branch_id' => null,
                'fullname' => 'Super Admin',
                'phone' => null,
                'password_hash' => Hash::make('password'),
                'member_code' => 'ADM-00001',
                'profile_type_id' => $profileTypeId,
                'institution_name' => 'Lumina Library System',
                'major_name' => 'System Administration',
                'current_level' => 'Administrator',
                'staff_position' => 'System Administrator',
                'staff_library_id' => null,
                'is_verified' => true,
                'is_active' => true,
            ]
        );

        if ($superAdmin->trashed()) {
            $superAdmin->restore();
        }

        UserRole::query()->updateOrCreate(
            ['user_id' => $superAdmin->id, 'role_id' => $superRole->id],
            [
                'id' => (string) Str::uuid(),
                'assigned_at' => now(),
                'assigned_by' => null,
                'is_active' => true,
                'deleted_at' => null,
            ]
        );

        $this->command?->info('Super Admin ready: super@lumina.ac.id / password');
    }
}
