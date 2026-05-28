<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';

        DB::table('banners')->insert([
            [
                'id'          => Str::uuid()->toString(),
                'title'       => 'Selamat Datang di Lumina Library',
                'subtitle'    => 'Perpustakaan digital kampus terbaik — pinjam buku semudah tap!',
                'image_path'   => 'https://picsum.photos/seed/banner-welcome/800/400',
                'image_is_external' => true,
                'action_url'  => null,
                'sort_order'  => 1,
                'is_active'   => true,
                'start_date'  => $now->copy()->subDays(30)->toDateString(),
                'end_date'    => null,
                'created_at'  => $now->copy()->subDays(30),
                'updated_at'  => $now,
                'deleted_at'  => null,
                'created_by'  => $admin,
                'updated_by'  => $admin,
            ],
            [
                'id'          => Str::uuid()->toString(),
                'title'       => 'Koleksi Baru Bulan Ini',
                'subtitle'    => '50+ buku baru telah ditambahkan ke koleksi perpustakaan',
                'image_path'   => 'https://picsum.photos/seed/banner-new-books/800/400',
                'image_is_external' => true,
                'action_url'  => '/catalog?sort=newest',
                'sort_order'  => 2,
                'is_active'   => true,
                'start_date'  => $now->copy()->subDays(7)->toDateString(),
                'end_date'    => null,
                'created_at'  => $now->copy()->subDays(7),
                'updated_at'  => $now,
                'deleted_at'  => null,
                'created_by'  => $admin,
                'updated_by'  => $admin,
            ],
            [
                'id'          => Str::uuid()->toString(),
                'title'       => 'Event: Bedah Buku Sapiens',
                'subtitle'    => 'Sabtu, 7 Juni 2026 — Aula Utama Central Library',
                'image_path'   => 'https://picsum.photos/seed/banner-event/800/400',
                'image_is_external' => true,
                'action_url'  => null,
                'sort_order'  => 3,
                'is_active'   => true,
                'start_date'  => $now->toDateString(),
                'end_date'    => $now->copy()->addDays(10)->toDateString(),
                'created_at'  => $now,
                'updated_at'  => $now,
                'deleted_at'  => null,
                'created_by'  => $admin,
                'updated_by'  => $admin,
            ],
        ]);
    }
}
