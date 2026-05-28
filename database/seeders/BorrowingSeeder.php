<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 5 transaksi peminjaman — menunjukkan SELURUH lifecycle:
 *
 * 1. PENDING   → Budi baru checkout, menunggu staff scan QR
 * 2. ACTIVE    → Rina sedang meminjam, masih dalam batas waktu
 * 3. ACTIVE    → Dimas sedang meminjam, hampir jatuh tempo
 * 4. RETURNED  → Budi sudah mengembalikan (sukses, tepat waktu)
 * 5. OVERDUE   → Putri melewati batas pengembalian (denda berlaku)
 */
class BorrowingSeeder extends Seeder
{
    public function run(): void
    {
        $now    = Carbon::now();
        $budi   = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina   = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas  = 'c1b2c3d4-0003-4000-8000-000000000003';
        $sarah  = 'c1b2c3d4-0003-4000-8000-000000000004';
        $ahmad  = 'c1b2c3d4-0003-4000-8000-000000000005';
        $putri  = 'c1b2c3d4-0003-4000-8000-000000000007';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east    = 'd1b2c3d4-0004-4000-8000-000000000002';

        DB::table('borrowings')->insert([
            // ── 1. PENDING: Budi just checked out ──
            [
                'id'                => 'a3b2c3d4-0008-4000-8000-000000000001',
                'borrowing_code'    => 'BRW-2026-0001',
                'user_id'           => $budi,
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'borrow_date'       => $now->toDateString(),
                'due_date'          => $now->copy()->addDays(14)->toDateString(),
                'return_date'       => null,
                'status'            => 'pending',
                'notes'             => 'Checkout dari cart — menunggu verifikasi staff di perpustakaan.',
                'approved_by'       => null,
                'approved_at'       => null,
                'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null,
                'created_by' => $budi, 'updated_by' => null,
            ],
            // ── 2. ACTIVE: Rina sedang meminjam ──
            [
                'id'                => 'a3b2c3d4-0008-4000-8000-000000000002',
                'borrowing_code'    => 'BRW-2026-0002',
                'user_id'           => $rina,
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'borrow_date'       => $now->copy()->subDays(10)->toDateString(),
                'due_date'          => $now->copy()->addDays(4)->toDateString(),
                'return_date'       => null,
                'status'            => 'active',
                'notes'             => null,
                'approved_by'       => $sarah,
                'approved_at'       => $now->copy()->subDays(10),
                'created_at' => $now->copy()->subDays(10), 'updated_at' => $now->copy()->subDays(10), 'deleted_at' => null,
                'created_by' => $rina, 'updated_by' => $sarah,
            ],
            // ── 3. ACTIVE: Dimas, hampir jatuh tempo ──
            [
                'id'                => 'a3b2c3d4-0008-4000-8000-000000000003',
                'borrowing_code'    => 'BRW-2026-0003',
                'user_id'           => $dimas,
                'branch_id'         => $east,
                'library_branch_id' => $east,
                'borrow_date'       => $now->copy()->subDays(12)->toDateString(),
                'due_date'          => $now->copy()->addDays(2)->toDateString(),
                'return_date'       => null,
                'status'            => 'active',
                'notes'             => null,
                'approved_by'       => $ahmad,
                'approved_at'       => $now->copy()->subDays(12),
                'created_at' => $now->copy()->subDays(12), 'updated_at' => $now->copy()->subDays(12), 'deleted_at' => null,
                'created_by' => $dimas, 'updated_by' => $ahmad,
            ],
            // ── 4. RETURNED: Budi sukses kembalikan ──
            [
                'id'                => 'a3b2c3d4-0008-4000-8000-000000000004',
                'borrowing_code'    => 'BRW-2026-0004',
                'user_id'           => $budi,
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'borrow_date'       => $now->copy()->subDays(30)->toDateString(),
                'due_date'          => $now->copy()->subDays(16)->toDateString(),
                'return_date'       => $now->copy()->subDays(18)->toDateString(), // 2 hari lebih awal!
                'status'            => 'returned',
                'notes'             => 'Dikembalikan tepat waktu.',
                'approved_by'       => $sarah,
                'approved_at'       => $now->copy()->subDays(30),
                'created_at' => $now->copy()->subDays(30), 'updated_at' => $now->copy()->subDays(18), 'deleted_at' => null,
                'created_by' => $budi, 'updated_by' => $sarah,
            ],
            // ── 5. OVERDUE: Putri terlambat ──
            [
                'id'                => 'a3b2c3d4-0008-4000-8000-000000000005',
                'borrowing_code'    => 'BRW-2026-0005',
                'user_id'           => $putri,
                'branch_id'         => $central,
                'library_branch_id' => $central,
                'borrow_date'       => $now->copy()->subDays(20)->toDateString(),
                'due_date'          => $now->copy()->subDays(6)->toDateString(), // 6 hari lewat!
                'return_date'       => null,
                'status'            => 'overdue',
                'notes'             => null,
                'approved_by'       => $sarah,
                'approved_at'       => $now->copy()->subDays(20),
                'created_at' => $now->copy()->subDays(20), 'updated_at' => $now->copy()->subDays(6), 'deleted_at' => null,
                'created_by' => $putri, 'updated_by' => null,
            ],
        ]);
    }
}
