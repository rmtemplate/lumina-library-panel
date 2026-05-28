<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Audit trail perubahan status setiap borrowing item.
 * Menunjukkan alur status: null → pending → active → returned/overdue
 */
class BorrowingStatusHistorySeeder extends Seeder
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

        $item = fn(int $n) => sprintf('a4b2c3d4-0009-4000-8000-%012d', $n);

        $histories = [
            // ── Item 1: Sapiens (pending, Budi) ──
            [$item(1), null, 'pending', $now, $budi, 'Checkout dari cart'],

            // ── Item 2: Atomic Habits (pending, Budi) ──
            [$item(2), null, 'pending', $now, $budi, 'Checkout dari cart'],

            // ── Item 3: Laskar Pelangi (active, Rina) ──
            [$item(3), null,      'pending', $now->copy()->subDays(10), $rina,  'Checkout dari cart'],
            [$item(3), 'pending', 'active',  $now->copy()->subDays(10), $sarah, 'Disetujui oleh petugas setelah scan QR'],

            // ── Item 4: Clean Code (active, Dimas) ──
            [$item(4), null,      'pending', $now->copy()->subDays(12), $dimas, 'Checkout dari cart'],
            [$item(4), 'pending', 'active',  $now->copy()->subDays(12), $ahmad, 'Disetujui oleh pustakawan'],

            // ── Item 5: Pragmatic Programmer (active, Dimas) ──
            [$item(5), null,      'pending', $now->copy()->subDays(12), $dimas, 'Checkout dari cart'],
            [$item(5), 'pending', 'active',  $now->copy()->subDays(12), $ahmad, 'Disetujui oleh pustakawan'],

            // ── Item 6: Bumi Manusia (returned, Budi) — full lifecycle ──
            [$item(6), null,      'pending',  $now->copy()->subDays(30), $budi,  'Checkout dari cart'],
            [$item(6), 'pending', 'active',   $now->copy()->subDays(30), $sarah, 'Disetujui oleh petugas'],
            [$item(6), 'active',  'returned', $now->copy()->subDays(18), $sarah, 'Buku dikembalikan dalam kondisi baik'],

            // ── Item 7: Filosofi Teras (overdue, Putri) ──
            [$item(7), null,      'pending', $now->copy()->subDays(20), $putri, 'Checkout dari cart'],
            [$item(7), 'pending', 'active',  $now->copy()->subDays(20), $sarah, 'Disetujui oleh petugas'],
            [$item(7), 'active',  'overdue', $now->copy()->subDays(6),  null,   'Otomatis oleh sistem — melewati batas waktu pengembalian'],

            // ── Item 8: Laut Bercerita (overdue, Putri) ──
            [$item(8), null,      'pending', $now->copy()->subDays(20), $putri, 'Checkout dari cart'],
            [$item(8), 'pending', 'active',  $now->copy()->subDays(20), $sarah, 'Disetujui oleh petugas'],
            [$item(8), 'active',  'overdue', $now->copy()->subDays(6),  null,   'Otomatis oleh sistem — melewati batas waktu pengembalian'],
        ];

        foreach ($histories as $h) {
            DB::table('borrowing_status_histories')->insert([
                'id'                => Str::uuid()->toString(),
                'borrowing_item_id' => $h[0],
                'from_status'       => $h[1],
                'to_status'         => $h[2],
                'changed_at'        => $h[3],
                'changed_by'        => $h[4],
                'notes'             => $h[5],
            ]);
        }
    }
}
