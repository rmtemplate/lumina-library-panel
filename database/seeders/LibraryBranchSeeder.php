<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LibraryBranchSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';

        DB::table('library_branches')->insert([
            [
                'id'                    => 'd1b2c3d4-0004-4000-8000-000000000001',
                'code'                  => 'lib-central',
                'name'                  => 'Central Library - Academic Wing',
                'address'               => 'Jl. Akademik No. 1, Kampus Utama Universitas Lumina, Bandung 40132',
                'description'           => 'Perpustakaan utama kampus dengan koleksi terlengkap. Dilengkapi ruang baca, ruang diskusi, dan area digital.',
                'phone'                 => '022-1234567',
                'email'                 => 'central@library.lumina.ac.id',
                'image_path'            => 'https://picsum.photos/seed/lib-central/800/400',
                'image_is_external'     => true,
                'latitude'              => -6.9175000,
                'longitude'             => 107.6191000,
                'open_time'             => '08:00',
                'close_time'            => '20:00',
                'schedule_description'  => 'Senin - Jumat, 08.00 - 20.00',
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
                'deleted_at'            => null,
                'created_by'            => $admin,
                'updated_by'            => $admin,
            ],
            [
                'id'                    => 'd1b2c3d4-0004-4000-8000-000000000002',
                'code'                  => 'lib-east',
                'name'                  => 'East Campus Library',
                'address'               => 'Jl. Timur Raya No. 45, Kampus Timur Universitas Lumina, Bandung 40135',
                'description'           => 'Perpustakaan kampus timur dengan fokus koleksi teknik dan sains terapan.',
                'phone'                 => '022-7654321',
                'email'                 => 'east@library.lumina.ac.id',
                'image_path'            => 'https://picsum.photos/seed/lib-east/800/400',
                'image_is_external'     => true,
                'latitude'              => -6.9200000,
                'longitude'             => 107.6300000,
                'open_time'             => '08:00',
                'close_time'            => '17:00',
                'schedule_description'  => 'Senin - Sabtu, 08.00 - 17.00',
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
                'deleted_at'            => null,
                'created_by'            => $admin,
                'updated_by'            => $admin,
            ],
            [
                'id'                    => 'd1b2c3d4-0004-4000-8000-000000000003',
                'code'                  => 'lib-digital',
                'name'                  => 'Digital Learning Corner',
                'address'               => 'Gedung Innovation Hub Lt. 2, Kampus Utama Universitas Lumina, Bandung 40132',
                'description'           => 'Ruang baca digital dengan akses e-book, jurnal online, dan workstation.',
                'phone'                 => '022-9876543',
                'email'                 => 'digital@library.lumina.ac.id',
                'image_path'            => 'https://picsum.photos/seed/lib-digital/800/400',
                'image_is_external'     => true,
                'latitude'              => -6.9180000,
                'longitude'             => 107.6195000,
                'open_time'             => '09:00',
                'close_time'            => '21:00',
                'schedule_description'  => 'Setiap Hari, 09.00 - 21.00',
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
                'deleted_at'            => null,
                'created_by'            => $admin,
                'updated_by'            => $admin,
            ],
        ]);
    }
}
