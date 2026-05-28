<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 2 riwayat pemindahan buku antar rak.
 * Sesuai MoveBooksController & MoveReasonModel di Flutter.
 */
class BookMoveHistorySeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $sarah = 'c1b2c3d4-0003-4000-8000-000000000004';
        $ahmad = 'c1b2c3d4-0003-4000-8000-000000000005';

        DB::table('book_move_histories')->insert([
            // Clean Code dipindahkan dari Rak Teknologi ke Rak Referensi (Basement)
            [
                'id'                  => Str::uuid()->toString(),
                'book_id'             => 'f1b2c3d4-0006-4000-8000-000000000004', // Clean Code
                'source_rack_id'      => 'a2b2c3d4-0007-4000-8000-000000000002', // Rak Teknologi
                'destination_rack_id' => 'a2b2c3d4-0007-4000-8000-000000000004', // Rak Referensi Basement
                'reason'              => 'reorganization',
                'notes'               => 'Dipindahkan ke seksi referensi karena sering digunakan sebagai bahan ajar dosen.',
                'moved_by'            => $sarah,
                'moved_at'            => $now->copy()->subDays(15),
            ],
            // KBBI dipindahkan dari Rak Novel ke Rak Referensi (Basement)
            [
                'id'                  => Str::uuid()->toString(),
                'book_id'             => 'f1b2c3d4-0006-4000-8000-000000000014', // KBBI
                'source_rack_id'      => 'a2b2c3d4-0007-4000-8000-000000000001', // Rak Novel (salah taruh sebelumnya)
                'destination_rack_id' => 'a2b2c3d4-0007-4000-8000-000000000004', // Rak Referensi Basement
                'reason'              => 'new_collection',
                'notes'               => 'Koleksi referensi baru — dipindah dari rak novel ke rak referensi basement.',
                'moved_by'            => $ahmad,
                'moved_at'            => $now->copy()->subDays(10),
            ],
        ]);
    }
}
