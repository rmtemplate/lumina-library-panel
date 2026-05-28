<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * 8 users yang merepresentasikan seluruh tipe profil & role.
 *
 * Password admin web: password
 * Password staff/user seed dummy: password123
 *
 * Login test:
 *   Mahasiswa → budi.santoso@student.lumina.ac.id
 *   Staff     → sarah.jenkins@lumina.ac.id
 *   Librarian → ahmad.fadillah@lumina.ac.id
 *   Super Admin     → super@lumina.ac.id
 *   Admin Central   → admin.central@lumina.ac.id
 *   Admin East      → admin.east@lumina.ac.id
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now  = Carbon::now();
        $hash = Hash::make('password123');
        $adminHash = Hash::make('password');

        $central  = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east     = 'd1b2c3d4-0004-4000-8000-000000000002';
        $ptMhs    = 'a1b2c3d4-0001-4000-8000-000000000005'; // mahasiswa
        $ptSma    = 'a1b2c3d4-0001-4000-8000-000000000004'; // sma_smk_ma
        $ptKerja  = 'a1b2c3d4-0001-4000-8000-000000000006'; // bekerja
        $ptUmum   = 'a1b2c3d4-0001-4000-8000-000000000007'; // umum

        DB::table('users')->insert([
            // ─── MAHASISWA 1: Budi Santoso ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000001',
                'branch_id'          => $central,
                'fullname'          => 'Budi Santoso',
                'email'             => 'budi.santoso@student.lumina.ac.id',
                'phone'             => '081234567890',
                'password_hash'     => $hash,
                'member_code'       => '20210045678',
                'avatar_path'        => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBvUfI0qnCR0Mqxq5OZ1UI-AKMt4ELnsfDVWA8DmzZgHBmANY-xDLsZpbv1OvvLuG8Indk0dXvCyW-JpSN85Y3AbQudS-3q1Qr2ZoOhPP93jXO3yaIM99_QLoM1LmQhpmry2TZCl0_reyaifv607bFXOCfTwCqytUn0rd1phuWfpZhBNg16w6tqc1-6BMFhZPG8RoSfoZC6XIhx_DqZYAJA8v6Qd_Hf-TBMLQWEMsISywvBSI7K9nd6zYQ5N4q90rt83KJAUUYJOa4P',
                'avatar_is_external' => true,
                'profile_type_id'   => $ptMhs,
                'institution_name'  => 'Universitas Lumina',
                'major_name'        => 'Teknik Informatika',
                'current_level'     => 'Semester 6',
                'staff_position'    => null,
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now,
                'created_at'        => $now->copy()->subMonths(6),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],
            // ─── MAHASISWA 2: Rina Wulandari ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000002',
                'branch_id'          => $central,
                'fullname'          => 'Rina Wulandari',
                'email'             => 'rina.wulandari@student.lumina.ac.id',
                'phone'             => '081298765432',
                'password_hash'     => $hash,
                'member_code'       => '20220012345',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptMhs,
                'institution_name'  => 'Universitas Lumina',
                'major_name'        => 'Sistem Informasi',
                'current_level'     => 'Semester 4',
                'staff_position'    => null,
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subDays(2),
                'created_at'        => $now->copy()->subMonths(4),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],
            // ─── MAHASISWA 3: Dimas Prasetyo ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000003',
                'branch_id'          => $central,
                'fullname'          => 'Dimas Prasetyo',
                'email'             => 'dimas.prasetyo@student.lumina.ac.id',
                'phone'             => '081377788899',
                'password_hash'     => $hash,
                'member_code'       => '20230067890',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptMhs,
                'institution_name'  => 'Universitas Lumina',
                'major_name'        => 'Desain Komunikasi Visual',
                'current_level'     => 'Semester 2',
                'staff_position'    => null,
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subDay(),
                'created_at'        => $now->copy()->subMonths(2),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],
            // ─── STAFF: Sarah Jenkins ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000004',
                'branch_id'          => $central,
                'fullname'          => 'Sarah Jenkins',
                'email'             => 'sarah.jenkins@lumina.ac.id',
                'phone'             => '081298765400',
                'password_hash'     => $hash,
                'member_code'       => 'STF-88291',
                'avatar_path'        => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBErNDUhVIPTqmPjcyS1_K6AwClAa2j9oVkgIH9tJNHXdOtTGuHfDDrDK0YXw9GzcJg1OE5wjvw7PGOPhw2Rlz5HX4m5b7Xgr-2q-I8FlCxuoqbk6bNlGUh7jHJneSGfghQLvkY6lxIOUWIvubuo4GIsfHWb_nkCJrCYOlF-A4Vnr_BP4KCsRbkBxsJ6OuFPaODjJR7ZBKcxZzSD5GYFw26vBguz-msysHR_GSXPfTXUdaqur78_nwxKVxgnGKPufaD412b8zL-WBrU',
                'avatar_is_external' => true,
                'profile_type_id'   => $ptKerja,
                'institution_name'  => 'Lumina Library',
                'major_name'        => 'Library Operations',
                'current_level'     => 'Senior Librarian',
                'staff_position'    => 'Senior Librarian',
                'staff_library_id'  => $central,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now,
                'created_at'        => $now->copy()->subYear(),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => 'c1b2c3d4-0003-4000-8000-000000000006',
                'updated_by'        => null,
            ],
            // ─── LIBRARIAN: Ahmad Fadillah ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000005',
                'branch_id'          => $central,
                'fullname'          => 'Ahmad Fadillah',
                'email'             => 'ahmad.fadillah@lumina.ac.id',
                'phone'             => '081311223344',
                'password_hash'     => $hash,
                'member_code'       => 'STF-77182',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptKerja,
                'institution_name'  => 'Lumina Library',
                'major_name'        => 'Library Management',
                'current_level'     => 'Head Librarian',
                'staff_position'    => 'Head Librarian',
                'staff_library_id'  => $central,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subHours(3),
                'created_at'        => $now->copy()->subYear(),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => 'c1b2c3d4-0003-4000-8000-000000000006',
                'updated_by'        => null,
            ],
            // ─── ADMIN: Super Admin ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000006',
                'branch_id'          => null,
                'fullname'          => 'Super Admin',
                'email'             => 'super@lumina.ac.id',
                'phone'             => null,
                'password_hash'     => $adminHash,
                'member_code'       => 'ADM-00001',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptUmum,
                'institution_name'  => 'Lumina Library System',
                'major_name'        => 'System Administration',
                'current_level'     => 'Administrator',
                'staff_position'    => 'System Administrator',
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subDays(3),
                'created_at'        => $now->copy()->subYears(2),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],

            // ─── ADMIN BRANCH: Central Library ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000009',
                'branch_id'          => $central,
                'fullname'          => 'Admin Central Library',
                'email'             => 'admin.central@lumina.ac.id',
                'phone'             => '081200000901',
                'password_hash'     => $adminHash,
                'member_code'       => 'ADM-CENTRAL-001',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptUmum,
                'institution_name'  => 'Lumina Library Central Branch',
                'major_name'        => 'Branch Administration',
                'current_level'     => 'Admin Branch',
                'staff_position'    => 'Branch Administrator',
                'staff_library_id'  => $central,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subHours(4),
                'created_at'        => $now->copy()->subYear(),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => 'c1b2c3d4-0003-4000-8000-000000000006',
                'updated_by'        => null,
            ],
            // ─── ADMIN BRANCH: East Campus ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000010',
                'branch_id'          => $east,
                'fullname'          => 'Admin East Campus Library',
                'email'             => 'admin.east@lumina.ac.id',
                'phone'             => '081200000902',
                'password_hash'     => $adminHash,
                'member_code'       => 'ADM-EAST-001',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptUmum,
                'institution_name'  => 'Lumina Library East Branch',
                'major_name'        => 'Branch Administration',
                'current_level'     => 'Admin Branch',
                'staff_position'    => 'Branch Administrator',
                'staff_library_id'  => $east,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subHours(5),
                'created_at'        => $now->copy()->subYear(),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => 'c1b2c3d4-0003-4000-8000-000000000006',
                'updated_by'        => null,
            ],
            // ─── SMA: Putri Handayani ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000007',
                'branch_id'          => $central,
                'fullname'          => 'Putri Handayani',
                'email'             => 'putri.handayani@student.lumina.ac.id',
                'phone'             => '081455566677',
                'password_hash'     => $hash,
                'member_code'       => '20240011223',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptSma,
                'institution_name'  => 'SMA Negeri 1 Bandung',
                'major_name'        => 'IPA',
                'current_level'     => 'Kelas 11',
                'staff_position'    => null,
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subDays(7),
                'created_at'        => $now->copy()->subDays(25),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],
            // ─── BEKERJA: Reza Firmansyah ───
            [
                'id'                => 'c1b2c3d4-0003-4000-8000-000000000008',
                'branch_id'          => $central,
                'fullname'          => 'Reza Firmansyah',
                'email'             => 'reza.firmansyah@gmail.com',
                'phone'             => '081599900011',
                'password_hash'     => $hash,
                'member_code'       => 'PUB-55001',
                'avatar_path'        => null,
                'avatar_is_external' => false,
                'profile_type_id'   => $ptKerja,
                'institution_name'  => 'PT Teknologi Nusantara',
                'major_name'        => 'Engineering Division',
                'current_level'     => 'Senior Developer',
                'staff_position'    => null,
                'staff_library_id'  => null,
                'is_verified'       => true,
                'is_active'         => true,
                'last_login_at'     => $now->copy()->subDays(10),
                'created_at'        => $now->copy()->subDays(20),
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => null,
                'updated_by'        => null,
            ],
        ]);
    }
}
