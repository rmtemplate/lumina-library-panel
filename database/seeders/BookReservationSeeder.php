<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 1 reservasi aktif — Dimas antri buku Sapiens yang stoknya habis di Central.
 */
class BookReservationSeeder extends Seeder
{
    public function run(): void
    {
        $now     = Carbon::now();
        $dimas   = 'c1b2c3d4-0003-4000-8000-000000000003';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';

        DB::table('book_reservations')->insert([
            'id'                => Str::uuid()->toString(),
            'user_id'           => $dimas,
            'book_id'           => 'f1b2c3d4-0006-4000-8000-000000000003', // Sapiens (stok 0 di Central)
            'library_branch_id' => $central,
            'queue_position'    => 1,
            'status'            => 'waiting',
            'reserved_at'       => $now->copy()->subDays(2),
            'available_at'      => null, // belum tersedia
            'expires_at'        => null,
            'fulfilled_at'      => null,
            'cancelled_at'      => null,
            'created_at'        => $now->copy()->subDays(2),
            'updated_at'        => $now,
        ]);
    }
}
