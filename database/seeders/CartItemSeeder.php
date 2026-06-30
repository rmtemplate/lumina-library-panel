<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Items di cart — menunjukkan flow sebelum checkout.
 *
 * Budi punya 3 item: 2 dari cabang central, 1 dari cabang east.
 * Rina punya 1 item dari cabang central.
 *
 * Catatan: is_selected dihapus dari skema (migration 2026_05_31_173946).
 * State selected dikelola sepenuhnya di sisi Flutter/client.
 */
class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $now     = Carbon::now();
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east    = 'd1b2c3d4-0004-4000-8000-000000000002';

        DB::table('cart_items')->insert([
            // ── Budi's cart ──────────────────────────────────────────────
            [
                'id'        => Str::uuid()->toString(),
                'cart_id'   => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'   => 'f1b2c3d4-0006-4000-8000-000000000003', // Sapiens
                'branch_id' => $central,
                'added_at'  => $now->copy()->subDays(2),
            ],
            [
                'id'        => Str::uuid()->toString(),
                'cart_id'   => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'   => 'f1b2c3d4-0006-4000-8000-000000000005', // Atomic Habits
                'branch_id' => $central,
                'added_at'  => $now->copy()->subDays(2),
            ],
            [
                'id'        => Str::uuid()->toString(),
                'cart_id'   => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'   => 'f1b2c3d4-0006-4000-8000-000000000004', // Clean Code
                'branch_id' => $east,
                'added_at'  => $now->copy()->subDay(),
            ],
            // ── Rina's cart ───────────────────────────────────────────────
            [
                'id'        => Str::uuid()->toString(),
                'cart_id'   => 'a6b2c3d4-000b-4000-8000-000000000002',
                'book_id'   => 'f1b2c3d4-0006-4000-8000-000000000006', // Filosofi Teras
                'branch_id' => $central,
                'added_at'  => $now->copy()->subDays(3),
            ],
        ]);
    }
}
