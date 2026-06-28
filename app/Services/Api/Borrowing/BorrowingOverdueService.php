<?php

namespace App\Services\Api\Borrowing;

use App\Events\Borrowing\BorrowingOverdue;
use App\Models\Borrowing;
use App\Models\BorrowingPayment;
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

            // BUG FIX: due_date never changes once a borrowing is checked
            // out, so "due_date < today" stays true forever once a loan is
            // late — even after the member pays the overdue fine and
            // markPaid() resets status back to ACTIVE so they can return
            // the books. Without this guard, the very next read-path call
            // to flagOne()/flagDueOverdueBorrowings() (which runs on EVERY
            // MyBookService::findDetail()/paginate() call) immediately
            // re-flags the same borrowing back to WAITING_PAYMENT, undoing
            // the payment and re-blocking the Return QR action — this was
            // the root cause of "payment shows Lunas but borrowing stays
            // WAITING_PAYMENT". A PAID overdue_fine payment for this
            // borrowing means this exact overdue period has already been
            // settled, so skip re-flagging.
            $alreadySettled = BorrowingPayment::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('purpose', BorrowingPayment::PURPOSE_OVERDUE_FINE)
                ->where('status', BorrowingPayment::PAID)
                ->exists();

            if ($alreadySettled) {
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
