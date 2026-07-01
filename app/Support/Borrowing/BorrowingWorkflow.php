<?php

namespace App\Support\Borrowing;

use App\Models\Borrowing;

/**
 * Single source of truth for what the member's app may DO with a borrowing
 * right now, derived ONLY from the borrowing's real status (never from
 * overdue_days / total_fine / a display status). Both the My Books list and
 * the Borrow Detail response embed this so the client renders strictly what
 * the backend allows — see docs/return-flow.md.
 *
 * `status` = what the borrowing IS. `workflow` = where it is in the return
 * flow. `actions` = which buttons may show. The client never infers these.
 */
class BorrowingWorkflow
{
    /**
     * @return array{workflow: array, actions: array}
     */
    public static function resolve(string $status): array
    {
        [$step, $nextAction, $label, $description] = self::step($status);

        return [
            'workflow' => [
                'current_step' => $step,
                'next_action' => $nextAction,
                'step_label' => $label,
                'step_description' => $description,
            ],
            'actions' => [
                'can_generate_return_qr' => self::canGenerateReturnQr($status),
                'can_refresh_qr' => self::canGenerateReturnQr($status) || $status === Borrowing::AWAITING_PICKUP,
                'can_pay_overdue' => $status === Borrowing::WAITING_OVERDUE_PAYMENT
                    || $status === Borrowing::WAITING_PAYMENT,
                'can_pay_damage' => $status === Borrowing::WAITING_DAMAGE_PAYMENT,
                'can_cancel' => in_array($status, [Borrowing::PENDING, Borrowing::AWAITING_PICKUP], true),
                'can_view_payment_history' => in_array($status, [
                    Borrowing::WAITING_OVERDUE_PAYMENT,
                    Borrowing::BOOK_INSPECTION,
                    Borrowing::WAITING_DAMAGE_PAYMENT,
                    Borrowing::COMPLETED,
                    Borrowing::RETURNED,
                    Borrowing::RETURNED_WITH_FINE,
                    Borrowing::WAITING_PAYMENT,
                ], true),
            ],
        ];
    }

    private static function canGenerateReturnQr(string $status): bool
    {
        // ALWAYS allowed while the loan is out — even months overdue. Overdue is
        // only charged AFTER staff scans (waiting_overdue_payment), never before.
        return in_array($status, [
            Borrowing::ACTIVE,
            Borrowing::READY_FOR_RETURN,
            Borrowing::OVERDUE,
        ], true);
    }

    /**
     * @return array{0:string,1:string,2:string,3:string} [step, next_action, label, description]
     */
    private static function step(string $status): array
    {
        return match ($status) {
            Borrowing::PENDING => [
                'pending', 'wait_confirmation',
                'Menunggu Konfirmasi', 'Peminjaman sedang diproses oleh perpustakaan.',
            ],
            Borrowing::AWAITING_PICKUP => [
                'awaiting_pickup', 'pickup',
                'Siap Diambil', 'Tunjukkan QR pengambilan ke petugas.',
            ],
            Borrowing::ACTIVE, Borrowing::READY_FOR_RETURN => [
                'borrowed', 'generate_return_qr',
                'Sedang Dipinjam', 'Buat QR pengembalian saat mengembalikan buku.',
            ],
            Borrowing::OVERDUE => [
                'overdue', 'generate_return_qr',
                'Terlambat', 'Buku terlambat. Buat QR pengembalian — denda dibayar setelah petugas memindai.',
            ],
            Borrowing::RETURN_RECEIVED, Borrowing::RETURN_PENDING_VERIFICATION => [
                'return_received', 'wait_verification',
                'Buku Diterima', 'Buku Anda telah diterima. Menunggu verifikasi petugas.',
            ],
            Borrowing::WAITING_OVERDUE_PAYMENT => [
                'waiting_overdue_payment', 'pay_overdue',
                'Pembayaran Denda Keterlambatan', 'Bayar denda keterlambatan untuk melanjutkan inspeksi buku.',
            ],
            Borrowing::BOOK_INSPECTION => [
                'book_inspection', 'wait_inspection',
                'Inspeksi Buku', 'Petugas sedang memeriksa kondisi buku Anda.',
            ],
            Borrowing::WAITING_DAMAGE_PAYMENT => [
                'waiting_damage_payment', 'pay_damage',
                'Pembayaran Biaya Kerusakan', 'Bayar biaya kerusakan untuk menyelesaikan pengembalian.',
            ],
            Borrowing::WAITING_PAYMENT => [
                'waiting_payment', 'pay_overdue',
                'Menunggu Pembayaran', 'Selesaikan pembayaran untuk melanjutkan.',
            ],
            Borrowing::COMPLETED, Borrowing::RETURNED, Borrowing::RETURNED_WITH_FINE => [
                'completed', 'none',
                'Selesai', 'Peminjaman telah selesai.',
            ],
            Borrowing::EXPIRED => [
                'expired', 'none',
                'Kadaluwarsa', 'Reservasi telah kadaluwarsa.',
            ],
            Borrowing::CANCELLED => [
                'cancelled', 'none',
                'Dibatalkan', 'Peminjaman dibatalkan.',
            ],
            default => [
                'unknown', 'none', 'Peminjaman', '',
            ],
        };
    }
}
