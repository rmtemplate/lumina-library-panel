<?php

namespace App\Services;

use App\Models\ActivityLog;

/**
 * Centralized helper for creating activity log entries.
 * All callers should pass branch_id so logs stay branch-scoped.
 */
class ActivityLogService
{
    public function log(
        string $activityType,
        string $title,
        ?string $branchId = null,
        ?string $userId = null,
        ?string $subtitle = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'activity_type' => $activityType,
            'title' => $title,
            'subtitle' => $subtitle,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'metadata' => $metadata ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }

    public function logBorrow(string $branchId, string $userId, string $borrowingId, string $borrowingCode): ActivityLog
    {
        return $this->log(
            activityType: ActivityLog::BORROW,
            title: 'Peminjaman baru dibuat',
            branchId: $branchId,
            userId: $userId,
            subtitle: $borrowingCode,
            referenceType: 'borrowing',
            referenceId: $borrowingId
        );
    }

    public function logReturn(string $branchId, string $userId, string $borrowingId, string $borrowingCode, ?float $fineAmount = null): ActivityLog
    {
        return $this->log(
            activityType: ActivityLog::RETURN,
            title: $fineAmount > 0 ? 'Pengembalian buku — terdapat denda' : 'Pengembalian buku selesai',
            branchId: $branchId,
            userId: $userId,
            subtitle: $borrowingCode,
            referenceType: 'borrowing',
            referenceId: $borrowingId,
            metadata: $fineAmount > 0 ? ['fine_amount' => $fineAmount] : []
        );
    }

    public function logFine(string $branchId, string $userId, string $fineId, float $fineAmount): ActivityLog
    {
        return $this->log(
            activityType: ActivityLog::FINE,
            title: 'Denda dibuat',
            branchId: $branchId,
            userId: $userId,
            subtitle: 'Rp ' . number_format($fineAmount, 0, ',', '.'),
            referenceType: 'fine',
            referenceId: $fineId,
            metadata: ['fine_amount' => $fineAmount]
        );
    }

    public function logScan(string $branchId, string $staffId, string $scanType, string $referenceId): ActivityLog
    {
        return $this->log(
            activityType: ActivityLog::SCAN,
            title: 'QR Code di-scan: ' . $scanType,
            branchId: $branchId,
            userId: $staffId,
            referenceType: $scanType,
            referenceId: $referenceId
        );
    }

    public function logBookMove(string $branchId, string $staffId, string $bookId, string $bookTitle, string $fromRack, string $toRack): ActivityLog
    {
        return $this->log(
            activityType: 'book_move',
            title: 'Buku dipindahkan ke rak baru',
            branchId: $branchId,
            userId: $staffId,
            subtitle: $bookTitle,
            referenceType: 'book',
            referenceId: $bookId,
            metadata: ['from_rack' => $fromRack, 'to_rack' => $toRack]
        );
    }
}
