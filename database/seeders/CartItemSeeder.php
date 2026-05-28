<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Items di cart — menunjukkan flow sebelum checkout.
 *
 * Budi punya 3 item: 2 selected, 1 unselected.
 * Rina punya 1 item: selected.
 *
 * CartLibraryGroupModel di Flutter mengelompokkan berdasarkan library_branch_id.
 */
class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $now      = Carbon::now();
        $central  = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east     = 'd1b2c3d4-0004-4000-8000-000000000002';

        DB::table('cart_items')->insert([
            // ── Budi's cart ──
            [
                'id'                => Str::uuid()->toString(),
                'cart_id'           => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'           => 'f1b2c3d4-0006-4000-8000-000000000003', // Sapiens
                'library_branch_id' => $central,
                'is_selected'       => true,
                'added_at'          => $now->copy()->subDays(2),
            ],
            [
                'id'                => Str::uuid()->toString(),
                'cart_id'           => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'           => 'f1b2c3d4-0006-4000-8000-000000000005', // Atomic Habits
                'library_branch_id' => $central,
                'is_selected'       => true,
                'added_at'          => $now->copy()->subDays(2),
            ],
            [
                'id'                => Str::uuid()->toString(),
                'cart_id'           => 'a6b2c3d4-000b-4000-8000-000000000001',
                'book_id'           => 'f1b2c3d4-0006-4000-8000-000000000004', // Clean Code
                'library_branch_id' => $east, // dari perpustakaan berbeda!
                'is_selected'       => false,  // belum di-select
                'added_at'          => $now->copy()->subDay(),
            ],
            // ── Rina's cart ──
            [
                'id'                => Str::uuid()->toString(),
                'cart_id'           => 'a6b2c3d4-000b-4000-8000-000000000002',
                'book_id'           => 'f1b2c3d4-0006-4000-8000-000000000006', // Filosofi Teras
                'library_branch_id' => $central,
                'is_selected'       => true,
                'added_at'          => $now->copy()->subDays(3),
            ],
        ]);
    }
}
