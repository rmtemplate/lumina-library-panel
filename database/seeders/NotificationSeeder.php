<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 6 notifikasi yang menggambarkan semua tipe notifikasi.
 */
class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina  = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas = 'c1b2c3d4-0003-4000-8000-000000000003';
        $putri = 'c1b2c3d4-0003-4000-8000-000000000007';

        DB::table('notifications')->insert([
            // 1. Reminder untuk Rina (buku H-4)
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $rina,
                'type'            => 'borrowing_reminder',
                'title'           => 'Pengingat Pengembalian',
                'body'         => 'Buku "Laskar Pelangi" harus dikembalikan dalam 4 hari. Jangan sampai terlambat ya!',
                'is_read'         => false,
                'read_at'         => null,
                'reference_type'  => 'borrowing',
                'reference_id'    => 'a3b2c3d4-0008-4000-8000-000000000002',
                'created_at'      => $now->copy()->subHours(6),
            ],
            // 2. Reminder untuk Dimas (buku H-2)
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $dimas,
                'type'            => 'borrowing_reminder',
                'title'           => 'Pengingat Pengembalian',
                'body'         => 'Buku "Clean Code" dan "The Pragmatic Programmer" harus dikembalikan dalam 2 hari.',
                'is_read'         => false,
                'read_at'         => null,
                'reference_type'  => 'borrowing',
                'reference_id'    => 'a3b2c3d4-0008-4000-8000-000000000003',
                'created_at'      => $now->copy()->subHours(3),
            ],
            // 3. Overdue alert untuk Putri
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $putri,
                'type'            => 'overdue_alert',
                'title'           => '⚠️ Buku Terlambat!',
                'body'         => 'Anda memiliki 2 buku yang melewati batas pengembalian. Denda berjalan Rp 12.000. Segera kembalikan buku Anda.',
                'is_read'         => false,
                'read_at'         => null,
                'reference_type'  => 'borrowing',
                'reference_id'    => 'a3b2c3d4-0008-4000-8000-000000000005',
                'created_at'      => $now->copy()->subDays(1),
            ],
            // 4. Return confirmation untuk Budi (sudah dibaca)
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $budi,
                'type'            => 'return_confirmation',
                'title'           => 'Pengembalian Berhasil ✅',
                'body'         => 'Buku "Bumi Manusia" telah berhasil dikembalikan. Terima kasih sudah menjaga buku dengan baik!',
                'is_read'         => true,
                'read_at'         => $now->copy()->subDays(18),
                'reference_type'  => 'borrowing',
                'reference_id'    => 'a3b2c3d4-0008-4000-8000-000000000004',
                'created_at'      => $now->copy()->subDays(18),
            ],
            // 5. System announcement (sudah dibaca oleh Budi)
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $budi,
                'type'            => 'system_announcement',
                'title'           => '📚 Koleksi Baru!',
                'body'         => '50+ buku baru telah ditambahkan ke perpustakaan bulan ini. Cek katalog sekarang untuk melihat koleksi terbaru!',
                'is_read'         => true,
                'read_at'         => $now->copy()->subDays(5),
                'reference_type'  => null,
                'reference_id'    => null,
                'created_at'      => $now->copy()->subDays(7),
            ],
            // 6. Fine notice untuk Putri
            [
                'id'              => Str::uuid()->toString(),
                'user_id'         => $putri,
                'type'            => 'fine_notice',
                'title'           => '💰 Denda Keterlambatan',
                'body'         => 'Denda sebesar Rp 12.000 telah dikenakan untuk 2 buku yang terlambat. Silakan selesaikan pembayaran di perpustakaan.',
                'is_read'         => false,
                'read_at'         => null,
                'reference_type'  => 'fine',
                'reference_id'    => null,
                'created_at'      => $now->copy()->subDays(5),
            ],
        ]);
    }
}
