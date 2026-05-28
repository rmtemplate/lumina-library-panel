<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 10 activity log entries — menunjukkan recent activity di staff dashboard.
 * Sesuai LibraryActivityModel & StaffDashboardActivityModel di Flutter.
 */
class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $now    = Carbon::now();
        $budi   = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina   = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas  = 'c1b2c3d4-0003-4000-8000-000000000003';
        $sarah  = 'c1b2c3d4-0003-4000-8000-000000000004';
        $putri  = 'c1b2c3d4-0003-4000-8000-000000000007';
        $reza   = 'c1b2c3d4-0003-4000-8000-000000000008';

        $logs = [
            // Terbaru di atas
            [
                'type'  => 'login', 'user' => $budi,
                'title' => 'Budi Santoso login ke aplikasi',
                'sub'   => 'Device: Samsung Galaxy S23 Ultra',
                'ref_type' => 'user', 'ref_id' => $budi,
                'days'  => 0,
            ],
            [
                'type'  => 'borrow', 'user' => $budi,
                'title' => 'Budi Santoso checkout 2 buku',
                'sub'   => 'Sapiens, Atomic Habits — menunggu approval staff',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000001',
                'days'  => 0,
            ],
            [
                'type'  => 'overdue', 'user' => $putri,
                'title' => 'Putri Handayani — 2 buku overdue',
                'sub'   => 'Filosofi Teras, Laut Bercerita — denda berjalan Rp 12.000',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000005',
                'days'  => 6,
            ],
            [
                'type'  => 'fine', 'user' => $putri,
                'title' => 'Denda Rp 12.000 dikenakan ke Putri Handayani',
                'sub'   => '2 buku, masing-masing 6 hari × Rp 1.000',
                'ref_type' => 'fine', 'ref_id' => 'a5b2c3d4-000a-4000-8000-000000000001',
                'days'  => 6,
            ],
            [
                'type'  => 'scan', 'user' => $sarah,
                'title' => 'Sarah Jenkins scan QR BRW-2026-0002',
                'sub'   => 'Approve peminjaman Rina Wulandari — Laskar Pelangi',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000002',
                'days'  => 10,
            ],
            [
                'type'  => 'borrow', 'user' => $rina,
                'title' => 'Rina Wulandari meminjam Laskar Pelangi',
                'sub'   => 'Central Library — due date 4 hari lagi',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000002',
                'days'  => 10,
            ],
            [
                'type'  => 'borrow', 'user' => $dimas,
                'title' => 'Dimas Prasetyo checkout 2 buku',
                'sub'   => 'Clean Code, The Pragmatic Programmer — East Campus Library',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000003',
                'days'  => 12,
            ],
            [
                'type'  => 'return', 'user' => $budi,
                'title' => 'Budi Santoso mengembalikan Bumi Manusia',
                'sub'   => 'Dikembalikan 2 hari lebih awal — kondisi baik',
                'ref_type' => 'borrowing', 'ref_id' => 'a3b2c3d4-0008-4000-8000-000000000004',
                'days'  => 18,
            ],
            [
                'type'  => 'register', 'user' => $reza,
                'title' => 'Reza Firmansyah mendaftar sebagai anggota baru',
                'sub'   => 'Tipe profil: Sudah Bekerja — PT Teknologi Nusantara',
                'ref_type' => 'user', 'ref_id' => $reza,
                'days'  => 20,
            ],
            [
                'type'  => 'register', 'user' => $putri,
                'title' => 'Putri Handayani mendaftar sebagai anggota baru',
                'sub'   => 'Tipe profil: SMA / SMK / MA — SMA Negeri 1 Bandung',
                'ref_type' => 'user', 'ref_id' => $putri,
                'days'  => 25,
            ],
        ];

        foreach ($logs as $l) {
            DB::table('activity_logs')->insert([
                'id'             => Str::uuid()->toString(),
                'user_id'        => $l['user'],
                'activity_type'  => $l['type'],
                'title'          => $l['title'],
                'subtitle'       => $l['sub'],
                'reference_type' => $l['ref_type'],
                'reference_id'   => $l['ref_id'],
                'ip_address'     => '192.168.1.' . rand(100, 200),
                'created_at'     => $now->copy()->subDays($l['days']),
            ]);
        }
    }
}
