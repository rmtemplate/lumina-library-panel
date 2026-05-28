<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 2 denda — kedua milik Putri (overdue 2 buku, masing-masing 6 hari × Rp1000).
 */
class FineSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $putri = 'c1b2c3d4-0003-4000-8000-000000000007';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';

        DB::table('fines')->insert([
            // Denda 1: Filosofi Teras (6 hari overdue × Rp1.000)
            [
                'id'                => 'a5b2c3d4-000a-4000-8000-000000000001',
                'user_id'           => $putri,
                'branch_id'         => $central,
                'borrowing_item_id' => 'a4b2c3d4-0009-4000-8000-000000000007',
                'fine_amount'       => 6000.00,
                'overdue_days'      => 6,
                'status'            => 'unpaid',
                'paid_at'           => null,
                'paid_amount'       => null,
                'waived_reason'     => null,
                'notes'             => 'Denda otomatis — buku Filosofi Teras terlambat 6 hari.',
                'created_at'        => $now->copy()->subDays(6),
                'updated_at'        => $now,
                'deleted_at'        => null,
            ],
            // Denda 2: Laut Bercerita (6 hari overdue × Rp1.000)
            [
                'id'                => 'a5b2c3d4-000a-4000-8000-000000000002',
                'user_id'           => $putri,
                'branch_id'         => $central,
                'borrowing_item_id' => 'a4b2c3d4-0009-4000-8000-000000000008',
                'fine_amount'       => 6000.00,
                'overdue_days'      => 6,
                'status'            => 'unpaid',
                'paid_at'           => null,
                'paid_amount'       => null,
                'waived_reason'     => null,
                'notes'             => 'Denda otomatis — buku Laut Bercerita terlambat 6 hari.',
                'created_at'        => $now->copy()->subDays(6),
                'updated_at'        => $now,
                'deleted_at'        => null,
            ],
        ]);
    }
}
