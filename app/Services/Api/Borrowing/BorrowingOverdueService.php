<?php

namespace App\Services\Api\Borrowing;

use App\Events\Borrowing\BorrowingOverdue;
use App\Models\Borrowing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Automatically flips an ACTIVE borrowing to WAITING_PAYMENT the instant its
 * due_date passes — mirrors BorrowingReservationExpiryService's exact
 * hybrid pattern (scheduler + runtime read-path backstop), see
 * docs/scheduler.md and docs/borrowing-lifecycle.md.
 *
 * Deliberately does NOT create any Fine row here — the actual fine amount
 * is computed fresh, on demand, at payment-creation time
 * (BorrowingPaymentService::createCorePayment() → FineCalculator), so
 * there's never a stale pre-computed amount to keep in sync while the
 * borrowing sits unpaid and the overdue-day count keeps climbing.
 */
class BorrowingOverdueService
{
    public function __construct(
        private readonly FineCalculator $fineCalculator
    ) {
    }

    public function flagDueOverdueBorrowings(int $limit = 100): int
    {
        $borrowingIds = Borrowing::query()
            ->where('status', Borrowing::ACTIVE)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->limit($limit)
            ->pluck('id');

        $flaggedCount = 0;

        foreach ($borrowingIds as $borrowingId) {
            if ($this->flagOne((string) $borrowingId)) {
                $flaggedCount++;
            }
        }

        if ($flaggedCount > 0) {
            Log::info('[OVERDUE] Bulk overdue-flagging completed', [
                'flagged_count' => $flaggedCount,
                'limit' => $limit,
            ]);
        }

        return $flaggedCount;
    }

    public function flagOne(string $borrowingId): bool
    {
        return DB::transaction(function () use ($borrowingId) {
            $borrowing = Borrowing::query()
                ->where('id', $borrowingId)
                ->lockForUpdate()
                ->first();

            if (! $borrowing) {
                return false;
            }

            if ($borrowing->status !== Borrowing::ACTIVE) {
                return false;
            }

            $overdueDays = $this->fineCalculator->overdueDays($borrowing);

            if ($overdueDays <= 0) {
                return false;
            }

            $borrowing->forceFill([
                'status' => Borrowing::WAITING_PAYMENT,
                'updated_at' => now(),
            ])->save();

            Log::warning('[OVERDUE] Borrowing flagged as payment-required', [
                'borrowing_id' => $borrowing->id,
                'borrowing_code' => $borrowing->borrowing_code,
                'overdue_days' => $overdueDays,
                'due_date' => $borrowing->due_date?->toDateString(),
            ]);

            DB::afterCommit(function () use ($borrowing, $overdueDays) {
                BorrowingOverdue::dispatch($borrowing->user_id, $borrowing->id, $borrowing->borrowing_code, $overdueDays);

                Log::info('[WS] Broadcast sent: borrowing.overdue', [
                    'borrowing_id' => $borrowing->id,
                    'channel' => 'user.' . $borrowing->user_id,
                ]);
            });

            return true;
        }, 3);
    }
}
