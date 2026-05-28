<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 8 borrowing items across 5 borrowings.
 *
 * Flow per status:
 *   PENDING  → 2 items (Budi: Sapiens + Atomic Habits)
 *   ACTIVE   → 3 items (Rina: Laskar Pelangi, Dimas: Clean Code + Pragmatic)
 *   RETURNED → 1 item  (Budi: Bumi Manusia, kondisi baik)
 *   OVERDUE  → 2 items (Putri: Filosofi Teras + Laut Bercerita)
 */
class BorrowingItemSeeder extends Seeder
{
    public function run(): void
    {
        $now  = Carbon::now();
        $book = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);

        DB::table('borrowing_items')->insert([
            // ── Borrowing 1 (PENDING, Budi) ──
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000001',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000001',
                'book_id'          => $book(3), // Sapiens
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000001',
                'status'           => 'pending',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
            ],
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000002',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000001',
                'book_id'          => $book(5), // Atomic Habits
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000002',
                'status'           => 'pending',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
            ],

            // ── Borrowing 2 (ACTIVE, Rina) ──
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000003',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000002',
                'book_id'          => $book(1), // Laskar Pelangi
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000003',
                'status'           => 'active',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now->copy()->subDays(10), 'updated_at' => $now->copy()->subDays(10), 'deleted_at' => null,
            ],

            // ── Borrowing 3 (ACTIVE, Dimas) ──
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000004',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000003',
                'book_id'          => $book(4), // Clean Code
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000004',
                'status'           => 'active',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now->copy()->subDays(12), 'updated_at' => $now->copy()->subDays(12), 'deleted_at' => null,
            ],
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000005',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000003',
                'book_id'          => $book(9), // Pragmatic Programmer
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000005',
                'status'           => 'active',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now->copy()->subDays(12), 'updated_at' => $now->copy()->subDays(12), 'deleted_at' => null,
            ],

            // ── Borrowing 4 (RETURNED, Budi) ──
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000006',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000004',
                'book_id'          => $book(2), // Bumi Manusia
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000006',
                'status'           => 'returned',
                'return_date'      => $now->copy()->subDays(18)->toDateString(),
                'return_condition' => 'good',
                'notes'            => 'Buku dalam kondisi baik.',
                'created_at' => $now->copy()->subDays(30), 'updated_at' => $now->copy()->subDays(18), 'deleted_at' => null,
            ],

            // ── Borrowing 5 (OVERDUE, Putri) ──
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000007',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000005',
                'book_id'          => $book(6), // Filosofi Teras
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000007',
                'status'           => 'overdue',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now->copy()->subDays(20), 'updated_at' => $now->copy()->subDays(6), 'deleted_at' => null,
            ],
            [
                'id'               => 'a4b2c3d4-0009-4000-8000-000000000008',
                'borrowing_id'     => 'a3b2c3d4-0008-4000-8000-000000000005',
                'book_id'          => $book(10), // Laut Bercerita
                'book_item_id'     => 'a6b2c3d4-0010-4000-8000-000000000008',
                'status'           => 'overdue',
                'return_date'      => null,
                'return_condition' => null,
                'notes'            => null,
                'created_at' => $now->copy()->subDays(20), 'updated_at' => $now->copy()->subDays(6), 'deleted_at' => null,
            ],
        ]);
    }
}
