<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 6 rak di 2 perpustakaan (Central + East).
 * Data sesuai dengan RackModel di Flutter staff feature.
 */
class RackSeeder extends Seeder
{
    public function run(): void
    {
        $now     = Carbon::now();
        $admin   = 'c1b2c3d4-0003-4000-8000-000000000006';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east    = 'd1b2c3d4-0004-4000-8000-000000000002';

        $jktPusat  = 'd1b2c3d4-0004-4000-8000-000000000004';
        $jktSelatan = 'd1b2c3d4-0004-4000-8000-000000000005';
        $jktBarat   = 'd1b2c3d4-0004-4000-8000-000000000006';
        $jktTimur   = 'd1b2c3d4-0004-4000-8000-000000000007';
        $jktUtara   = 'd1b2c3d4-0004-4000-8000-000000000008';

        DB::table('racks')->insertOrIgnore([
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000001',
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'rack_code'         => '1RN002',
                'name'              => 'Rak Novel Utama',
                'section_name'      => 'Seksi Novel',
                'floor_label'       => 'Lantai 1',
                'area_label'        => 'Area Novel',
                'aisle_code'        => 'Lorong N-02',
                'capacity'          => 72,
                'is_basement'       => false,
                'notes'             => 'Rak utama untuk koleksi novel dan sastra. Termasuk psikologi populer.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000002',
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'rack_code'         => '1RT001',
                'name'              => 'Rak Teknologi',
                'section_name'      => 'Seksi Teknologi',
                'floor_label'       => 'Lantai 1',
                'area_label'        => 'Technology Zone',
                'aisle_code'        => 'Lorong T-01',
                'capacity'          => 80,
                'is_basement'       => false,
                'notes'             => 'Koleksi buku programming, software engineering, dan IT.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000003',
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'rack_code'         => '2RS001',
                'name'              => 'Rak Sains',
                'section_name'      => 'Seksi Sains',
                'floor_label'       => 'Lantai 2',
                'area_label'        => 'Science Corner',
                'aisle_code'        => 'Lorong S-01',
                'capacity'          => 64,
                'is_basement'       => false,
                'notes'             => 'Buku sains, matematika, dan sejarah.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000004',
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'rack_code'         => 'B1-REF-001',
                'name'              => 'Rak Referensi Basement',
                'section_name'      => 'Seksi Referensi',
                'floor_label'       => 'Basement B1',
                'area_label'        => 'Reference Zone',
                'aisle_code'        => 'Lorong R-01',
                'capacity'          => 60,
                'is_basement'       => true,
                'notes'             => 'Kamus, ensiklopedia, kitab, dan buku referensi. Area basement.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000005',
                'branch_id'         => $east,
                'library_branch_id' => $east,
                'rack_code'         => 'E1-FIK-001',
                'name'              => 'Rak Fiksi Timur',
                'section_name'      => 'Seksi Fiksi',
                'floor_label'       => 'Lantai 1',
                'area_label'        => 'Area Fiksi',
                'aisle_code'        => 'Lorong F-01',
                'capacity'          => 50,
                'is_basement'       => false,
                'notes'             => 'Koleksi fiksi kampus timur.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id'                => 'a2b2c3d4-0007-4000-8000-000000000006',
                'branch_id'         => $east,
                'library_branch_id' => $east,
                'rack_code'         => 'E1-BIS-001',
                'name'              => 'Rak Bisnis & Ekonomi',
                'section_name'      => 'Seksi Bisnis & Ekonomi',
                'floor_label'       => 'Lantai 1',
                'area_label'        => 'Business Corner',
                'aisle_code'        => 'Lorong B-01',
                'capacity'          => 40,
                'is_basement'       => false,
                'notes'             => 'Buku bisnis, ekonomi, dan manajemen.',
                'is_active'         => true,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],

            // ── Jakarta Pusat: 3 racks ──────────────────────────────────────
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000007', 'branch_id' => $jktPusat, 'library_branch_id' => $jktPusat,
                'rack_code' => 'JKP-FIK-001', 'name' => 'Rak Fiksi & Sastra', 'section_name' => 'Seksi Fiksi',
                'floor_label' => 'Lantai 1', 'area_label' => 'Area Sastra', 'aisle_code' => 'Lorong F-01',
                'capacity' => 80, 'is_basement' => false,
                'notes' => 'Novel, fiksi, sastra Indonesia, novel terjemahan.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000008', 'branch_id' => $jktPusat, 'library_branch_id' => $jktPusat,
                'rack_code' => 'JKP-TEK-001', 'name' => 'Rak Teknologi & Sains', 'section_name' => 'Seksi Teknologi',
                'floor_label' => 'Lantai 2', 'area_label' => 'Technology Corner', 'aisle_code' => 'Lorong T-01',
                'capacity' => 100, 'is_basement' => false,
                'notes' => 'Komputer, pemrograman, AI, sains, matematika, fisika, kimia, biologi.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000009', 'branch_id' => $jktPusat, 'library_branch_id' => $jktPusat,
                'rack_code' => 'JKP-SOC-001', 'name' => 'Rak Sosial & Humaniora', 'section_name' => 'Seksi Sosial',
                'floor_label' => 'Lantai 2', 'area_label' => 'Social Sciences', 'aisle_code' => 'Lorong S-01',
                'capacity' => 90, 'is_basement' => false,
                'notes' => 'Sejarah, hukum, politik, pendidikan, psikologi, bisnis, agama, filsafat.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],

            // ── Jakarta Selatan ─────────────────────────────────────────────
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000010', 'branch_id' => $jktSelatan, 'library_branch_id' => $jktSelatan,
                'rack_code' => 'JKS-GEN-001', 'name' => 'Rak Koleksi Umum', 'section_name' => 'Koleksi Umum',
                'floor_label' => 'Lantai 1', 'area_label' => 'Ruang Utama', 'aisle_code' => 'Lorong A-01',
                'capacity' => 120, 'is_basement' => false,
                'notes' => 'Semua kategori buku umum.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000011', 'branch_id' => $jktSelatan, 'library_branch_id' => $jktSelatan,
                'rack_code' => 'JKS-BIS-001', 'name' => 'Rak Bisnis & Ekonomi', 'section_name' => 'Bisnis',
                'floor_label' => 'Lantai 1', 'area_label' => 'Business Corner', 'aisle_code' => 'Lorong B-01',
                'capacity' => 60, 'is_basement' => false,
                'notes' => 'Bisnis, ekonomi, kewirausahaan, manajemen.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],

            // ── Jakarta Barat ────────────────────────────────────────────────
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000012', 'branch_id' => $jktBarat, 'library_branch_id' => $jktBarat,
                'rack_code' => 'JKB-GEN-001', 'name' => 'Rak Koleksi Umum', 'section_name' => 'Koleksi Umum',
                'floor_label' => 'Lantai 1', 'area_label' => 'Ruang Utama', 'aisle_code' => 'Lorong A-01',
                'capacity' => 100, 'is_basement' => false,
                'notes' => 'Koleksi umum termasuk fiksi, anak, dan komik.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],

            // ── Jakarta Timur ────────────────────────────────────────────────
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000013', 'branch_id' => $jktTimur, 'library_branch_id' => $jktTimur,
                'rack_code' => 'JKT-GEN-001', 'name' => 'Rak Koleksi Umum', 'section_name' => 'Koleksi Umum',
                'floor_label' => 'Lantai 1', 'area_label' => 'Ruang Utama', 'aisle_code' => 'Lorong A-01',
                'capacity' => 100, 'is_basement' => false,
                'notes' => 'Sejarah, agama, ilmu sosial, dan koleksi umum.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],

            // ── Jakarta Utara ────────────────────────────────────────────────
            [
                'id' => 'a2b2c3d4-0007-4000-8000-000000000014', 'branch_id' => $jktUtara, 'library_branch_id' => $jktUtara,
                'rack_code' => 'JKU-GEN-001', 'name' => 'Rak Koleksi Umum', 'section_name' => 'Koleksi Umum',
                'floor_label' => 'Lantai 1', 'area_label' => 'Ruang Utama', 'aisle_code' => 'Lorong A-01',
                'capacity' => 80, 'is_basement' => false,
                'notes' => 'Sastra, sejarah maritim, dan koleksi umum.',
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $admin, 'updated_by' => $admin,
            ],
        ]);
    }
}
