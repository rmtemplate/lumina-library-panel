<?php

namespace Database\Seeders;

use App\Models\BorrowingQrCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Sample QR codes untuk flow scan peminjaman dan pengembalian.
 * qr_type menggunakan value bisnis: minjam / kembalikan.
 */
class BorrowingQrCodeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';
        $sarah = 'c1b2c3d4-0003-4000-8000-000000000004';

        DB::table('borrowing_qr_codes')->insert([
            [
                'id'           => 'a8b2c3d4-001f-4000-8000-000000000001',
                'borrowing_id' => 'a3b2c3d4-0008-4000-8000-000000000001',
                'qr_code'      => 'QR-BRW-2026-0001-BORROW',
                'qr_type'      => BorrowingQrCode::TYPE_BORROW,
                'status'       => BorrowingQrCode::STATUS_ACTIVE,
                'payload'      => json_encode(['borrowing_code' => 'BRW-2026-0001', 'action' => BorrowingQrCode::TYPE_BORROW]),
                'expires_at'   => $now->copy()->addMinutes(30),
                'used_at'      => null,
                'used_by'      => null,
                'created_at'   => $now,
                'updated_at'   => $now,
                'deleted_at'   => null,
                'created_by'   => $admin,
                'updated_by'   => $admin,
            ],
            [
                'id'           => 'a8b2c3d4-001f-4000-8000-000000000002',
                'borrowing_id' => 'a3b2c3d4-0008-4000-8000-000000000002',
                'qr_code'      => 'QR-BRW-2026-0002-BORROW',
                'qr_type'      => BorrowingQrCode::TYPE_BORROW,
                'status'       => BorrowingQrCode::STATUS_USED,
                'payload'      => json_encode(['borrowing_code' => 'BRW-2026-0002', 'action' => BorrowingQrCode::TYPE_BORROW]),
                'expires_at'   => $now->copy()->subDays(10)->addMinutes(30),
                'used_at'      => $now->copy()->subDays(10),
                'used_by'      => $sarah,
                'created_at'   => $now->copy()->subDays(10),
                'updated_at'   => $now->copy()->subDays(10),
                'deleted_at'   => null,
                'created_by'   => $admin,
                'updated_by'   => $sarah,
            ],
            [
                'id'           => 'a8b2c3d4-001f-4000-8000-000000000003',
                'borrowing_id' => 'a3b2c3d4-0008-4000-8000-000000000002',
                'qr_code'      => 'QR-BRW-2026-0002-RETURN',
                'qr_type'      => BorrowingQrCode::TYPE_RETURN,
                'status'       => BorrowingQrCode::STATUS_ACTIVE,
                'payload'      => json_encode(['borrowing_code' => 'BRW-2026-0002', 'action' => BorrowingQrCode::TYPE_RETURN]),
                'expires_at'   => $now->copy()->addMinutes(30),
                'used_at'      => null,
                'used_by'      => null,
                'created_at'   => $now,
                'updated_at'   => $now,
                'deleted_at'   => null,
                'created_by'   => $admin,
                'updated_by'   => $admin,
            ],
            [
                'id'           => 'a8b2c3d4-001f-4000-8000-000000000004',
                'borrowing_id' => 'a3b2c3d4-0008-4000-8000-000000000004',
                'qr_code'      => 'QR-BRW-2026-0004-RETURN',
                'qr_type'      => BorrowingQrCode::TYPE_RETURN,
                'status'       => BorrowingQrCode::STATUS_USED,
                'payload'      => json_encode(['borrowing_code' => 'BRW-2026-0004', 'action' => BorrowingQrCode::TYPE_RETURN]),
                'expires_at'   => $now->copy()->subDays(18)->addMinutes(30),
                'used_at'      => $now->copy()->subDays(18),
                'used_by'      => $sarah,
                'created_at'   => $now->copy()->subDays(18),
                'updated_at'   => $now->copy()->subDays(18),
                'deleted_at'   => null,
                'created_by'   => $admin,
                'updated_by'   => $sarah,
            ],
            [
                'id'           => 'a8b2c3d4-001f-4000-8000-000000000005',
                'borrowing_id' => 'a3b2c3d4-0008-4000-8000-000000000005',
                'qr_code'      => 'QR-BRW-2026-0005-RETURN-EXPIRED',
                'qr_type'      => BorrowingQrCode::TYPE_RETURN,
                'status'       => BorrowingQrCode::STATUS_EXPIRED,
                'payload'      => json_encode(['borrowing_code' => 'BRW-2026-0005', 'action' => BorrowingQrCode::TYPE_RETURN]),
                'expires_at'   => $now->copy()->subMinutes(10),
                'used_at'      => null,
                'used_by'      => null,
                'created_at'   => $now->copy()->subHour(),
                'updated_at'   => $now->copy()->subMinutes(10),
                'deleted_at'   => null,
                'created_by'   => $admin,
                'updated_by'   => $admin,
            ],
        ]);
    }
}
