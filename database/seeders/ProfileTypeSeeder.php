<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfileTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $rows = [
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000001', 'code' => 'tk', 'label' => 'TK', 'desc' => 'Anak usia taman kanak-kanak.', 'inst' => 'Sekolah', 'major' => 'Kelompok', 'level' => 'Kelas', 'show_major' => false],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000002', 'code' => 'sd', 'label' => 'SD', 'desc' => 'Siswa sekolah dasar.', 'inst' => 'Sekolah', 'major' => 'Kurikulum', 'level' => 'Kelas', 'show_major' => false],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000003', 'code' => 'smp', 'label' => 'SMP', 'desc' => 'Siswa sekolah menengah pertama.', 'inst' => 'Sekolah', 'major' => 'Kurikulum', 'level' => 'Kelas', 'show_major' => false],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000004', 'code' => 'sma_smk', 'label' => 'SMA/SMK', 'desc' => 'Siswa sekolah menengah atas/kejuruan.', 'inst' => 'Sekolah', 'major' => 'Jurusan', 'level' => 'Kelas', 'show_major' => true],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000005', 'code' => 'mahasiswa', 'label' => 'Mahasiswa/i', 'desc' => 'Mahasiswa perguruan tinggi.', 'inst' => 'Kampus', 'major' => 'Program Studi', 'level' => 'Semester', 'show_major' => true],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000006', 'code' => 'pekerja', 'label' => 'Pekerja', 'desc' => 'Member yang sudah bekerja/profesional.', 'inst' => 'Perusahaan', 'major' => 'Bidang/Divisi', 'level' => 'Jabatan', 'show_major' => true],
            ['id' => 'a1b2c3d4-0001-4000-8000-000000000007', 'code' => 'umum', 'label' => 'Umum', 'desc' => 'Member umum tanpa segmen pendidikan/pekerjaan khusus.', 'inst' => 'Instansi', 'major' => 'Minat', 'level' => 'Level', 'show_major' => false],
        ];

        foreach ($rows as $i => $row) {
            DB::table('profile_types')->insert([
                'id' => $row['id'],
                'code' => $row['code'],
                'label' => $row['label'],
                'description' => $row['desc'],
                'institution_label' => $row['inst'],
                'major_label' => $row['major'],
                'level_label' => $row['level'],
                'show_institution' => true,
                'show_major' => $row['show_major'],
                'show_level' => true,
                'requires_institution' => false,
                'requires_major' => false,
                'requires_level' => false,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }
    }
}
