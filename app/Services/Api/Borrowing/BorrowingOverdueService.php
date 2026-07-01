<?php

namespace App\Services\Api\Borrowing;

use App\Events\Borrowing\BorrowingOverdue;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
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
        // Self-heal legacy data first: any loan stuck in the OLD pre-return
        // `waiting_payment` state whose books are still out belongs in the new
        // two-stage flow as plain OVERDUE (returnable — overdue is only charged
        // AFTER staff scan). Without this, such loans keep showing a payment
        // button before the QR is ever scanned.
        $this->normalizeStaleWaitingPayment($limit);

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

            // Per-id self-heal for the same stale legacy case as the bulk pass.
            if ($borrowing->status === Borrowing::WAITING_PAYMENT
                && $this->booksStillOut($borrowing)) {
                $borrowing->forceFill([
                    'status' => Borrowing::OVERDUE,
                    'updated_at' => now(),
                ])->save();

                Log::info('[OVERDUE] Normalized stale waiting_payment → overdue', [
                    'borrowing_id' => $borrowing->id,
                ]);

                return true;
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

            // Two-stage return flow: overdue does NOT force payment before the
            // books are returned. Flag to OVERDUE (display + still allows the
            // Return QR) — the overdue fine is only created/charged when staff
            // scan the Return QR (see StaffQrScanService::previewReturn).
            $borrowing->forceFill([
                'status' => Borrowing::OVERDUE,
                'updated_at' => now(),
            ])->save();

            Log::warning('[OVERDUE] Borrowing flagged overdue', [
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

    /**
     * Bulk-normalize legacy `waiting_payment` loans whose books are still out
     * back to `overdue`. A loan with an active item cannot legitimately be in a
     * return/damage payment state (the books aren't back), so any such row is a
     * stale pre-return overdue flag from the old flow — it belongs in `overdue`.
     */
    private function normalizeStaleWaitingPayment(int $limit): void
    {
        $ids = Borrowing::query()
            ->where('status', Borrowing::WAITING_PAYMENT)
            ->whereHas('items', fn ($q) => $q->where('status', BorrowingItem::ACTIVE))
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        Borrowing::query()->whereIn('id', $ids)->update([
            'status' => Borrowing::OVERDUE,
            'updated_at' => now(),
        ]);

        Log::info('[OVERDUE] Normalized stale waiting_payment → overdue (bulk)', [
            'count' => $ids->count(),
        ]);
    }

    private function booksStillOut(Borrowing $borrowing): bool
    {
        return BorrowingItem::query()
            ->where('borrowing_id', $borrowing->id)
            ->where('status', BorrowingItem::ACTIVE)
            ->exists();
    }
}
