<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Centralized service for creating in-app notifications.
 * Keeps all notification creation in one place to ensure consistency.
 * Staff notifications must include branch_id for proper scoping.
 */
class NotificationService
{
    // ── Member/User Notifications ──────────────────────────────────────────

    public function notifyBorrowingCreated(User $user, string $borrowingId, string $borrowingCode): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::GENERAL,
            title: 'Peminjaman berhasil dibuat',
            body: "Peminjaman #{$borrowingCode} berhasil. Segera ambil buku sebelum reservasi berakhir.",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyBorrowingPickedUp(User $user, string $borrowingId, string $borrowingCode): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::GENERAL,
            title: 'Buku berhasil diambil',
            body: "Peminjaman #{$borrowingCode} aktif. Selamat membaca!",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyBorrowingReturned(User $user, string $borrowingId, string $borrowingCode): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::RETURN_CONFIRMATION,
            title: 'Pengembalian buku berhasil',
            body: "Peminjaman #{$borrowingCode} telah selesai. Terima kasih!",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyFineCreated(User $user, string $borrowingId, float $fineAmount): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::FINE_NOTICE,
            title: 'Denda peminjaman dibuat',
            body: 'Terdapat denda sebesar Rp ' . number_format($fineAmount, 0, ',', '.') . '. Silakan selesaikan pembayaran.',
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyPaymentConfirmed(User $user, string $borrowingId, string $borrowingCode): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::FINE_NOTICE,
            title: 'Pembayaran denda berhasil',
            body: "Denda untuk peminjaman #{$borrowingCode} telah berhasil dibayar.",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyDueReminder(User $user, string $borrowingId, int $daysLeft, string $dueDate): Notification
    {
        $body = $daysLeft === 0
            ? "Jatuh tempo peminjaman Anda hari ini ({$dueDate})! Segera kembalikan buku."
            : "Buku Anda akan jatuh tempo dalam {$daysLeft} hari ({$dueDate}). Jangan lupa kembalikan tepat waktu.";

        return $this->create(
            userId: $user->id,
            type: Notification::BORROWING_REMINDER,
            title: $daysLeft === 0 ? 'Jatuh tempo hari ini!' : "Pengingat jatuh tempo dalam {$daysLeft} hari",
            body: $body,
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyOverdueAlert(User $user, string $borrowingId, int $overdueDays): Notification
    {
        return $this->create(
            userId: $user->id,
            type: Notification::OVERDUE_ALERT,
            title: 'Peminjaman terlambat dikembalikan',
            body: "Buku Anda sudah terlambat {$overdueDays} hari. Denda akan terus bertambah. Segera kembalikan.",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    // ── Staff Notifications (branch-scoped) ────────────────────────────────

    public function notifyStaffBorrowingCreated(string $branchId, Collection $staffList, string $borrowingId, string $borrowingCode, string $memberName): void
    {
        $this->notifyStaffGroup(
            staffList: $staffList,
            branchId: $branchId,
            type: Notification::STAFF_BORROWING,
            title: 'Peminjaman baru masuk',
            body: "{$memberName} membuat peminjaman #{$borrowingCode}. Siapkan buku untuk pengambilan.",
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyStaffReturnProcessed(string $branchId, Collection $staffList, string $borrowingId, string $borrowingCode, float $fineAmount): void
    {
        $body = $fineAmount > 0
            ? "Pengembalian #{$borrowingCode} diproses. Terdapat denda Rp " . number_format($fineAmount, 0, ',', '.') . '.'
            : "Pengembalian #{$borrowingCode} berhasil diproses tanpa denda.";

        $this->notifyStaffGroup(
            staffList: $staffList,
            branchId: $branchId,
            type: Notification::STAFF_RETURN,
            title: 'Buku berhasil dikembalikan',
            body: $body,
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function notifyStaffPaymentReceived(string $branchId, Collection $staffList, string $borrowingId, string $borrowingCode, float $amount): void
    {
        $this->notifyStaffGroup(
            staffList: $staffList,
            branchId: $branchId,
            type: Notification::STAFF_PAYMENT,
            title: 'Denda berhasil dibayar',
            body: "Denda untuk #{$borrowingCode} sebesar Rp " . number_format($amount, 0, ',', '.') . ' telah dibayar melalui Midtrans.',
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    // ── Internals ──────────────────────────────────────────────────────────

    public function create(
        string $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $branchId = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    private function notifyStaffGroup(
        Collection $staffList,
        string $branchId,
        string $type,
        string $title,
        string $body,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): void {
        foreach ($staffList as $staff) {
            $this->create(
                userId: $staff->id,
                type: $type,
                title: $title,
                body: $body,
                branchId: $branchId,
                referenceType: $referenceType,
                referenceId: $referenceId
            );
        }
    }
}
