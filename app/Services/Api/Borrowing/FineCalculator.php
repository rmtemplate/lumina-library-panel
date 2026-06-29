<?php

namespace App\Services\Api\Borrowing;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingPayment;
use App\Services\AppSettingService;
use Carbon\Carbon;

/**
 * Single source of truth for every fine/fee amount in the app — overdue
 * (per day), damage/lost (return condition), and pickup no-show (penalty).
 *
 * Extracted from what used to be private, duplicated logic spread across
 * StaffQrScanService::confirmReturn(), MyBookService::finePerDay(), and
 * StaffBorrowingService::finePerDay() — all three now delegate here instead
 * of each hardcoding their own AppSetting default.
 *
 * All amounts are looked up fresh on every call (AppSetting-backed,
 * DB-configurable without a deploy) — nothing here is cached or
 * pre-computed, so a fine amount is never stale by the time it's charged.
 */
class FineCalculator
{
    public function __construct(
        private readonly AppSettingService $appSettingService
    ) {
    }

    public function finePerDay(): float
    {
        return $this->appSettingService->getDecimal(
            'fine_per_day',
            (float) config('lumina.borrowing.fine_per_day_default', 5000)
        );
    }

    public function pickupNoShowFee(): float
    {
        return $this->appSettingService->getDecimal(
            'pickup_noshow_fee',
            (float) config('lumina.borrowing.pickup_noshow_fee', 10000)
        );
    }

    /**
     * @return array{fine_per_day: float, minor_damage_fee: float, major_damage_fee: float, lost_fee: float}
     */
    public function feeRules(): array
    {
        return [
            'fine_per_day' => $this->finePerDay(),
            'minor_damage_fee' => $this->appSettingService->getDecimal('minor_damage_fee', 5000),
            'major_damage_fee' => $this->appSettingService->getDecimal('major_damage_fee', 25000),
            'lost_fee' => $this->appSettingService->getDecimal('lost_fee', 75000),
        ];
    }

    public function overdueDays(Borrowing $borrowing): int
    {
        if (! $borrowing->due_date) {
            return 0;
        }

        $today = now()->startOfDay();
        $due = Carbon::parse($borrowing->due_date)->startOfDay();

        return $due->isPast() && $today->gt($due)
            ? (int) $due->diffInDays($today)
            : 0;
    }

    /**
     * THE single source of truth for "how much does this borrowing owe
     * right now for being late" — Overdue Days x Fine Per Day x active
     * item count. Every caller (MyBookService, StaffBorrowingService,
     * StaffQrScanService, BorrowingPaymentService) must go through this
     * method instead of re-deriving the formula inline — three of them
     * used to do exactly that under the name "estimated_fine", which is
     * how a Fine Calculator and Resource layer (MyBookService's display
     * value) silently drifted from this method's superset of correctness
     * whenever a caller counted "active items" differently. There is no
     * "estimate" — this number IS the fine, and it's also exactly what
     * gets charged (see BorrowingPaymentService::createFreshOverdueFines(),
     * which sums one Fine row per active item using these same two
     * primitives).
     *
     * Item count is resolved here, not passed in by the caller, precisely
     * to remove the one place callers could disagree (e.g. counting
     * `['active', 'pending']` items vs. `ACTIVE`-only items).
     */
    public function overdueFineAmount(Borrowing $borrowing): float
    {
        return $this->overdueDays($borrowing) * $this->finePerDay() * max($this->activeItemsCount($borrowing), 1);
    }

    /**
     * True when a live, accruing overdue fine should be displayed and
     * charged — the book is still physically out, the due_date has passed,
     * AND the overdue fine has not yet been settled.
     *
     * Ignores the DB status column by design: ACTIVE → WAITING_PAYMENT
     * (BorrowingOverdueService) and WAITING_PAYMENT → ACTIVE (markPaid)
     * both happen while the book is still physically out. After payment,
     * markPaid() resets the DB status to ACTIVE so the member can generate
     * a Return QR — but the due_date never changes, so `overdueDays > 0`
     * would still be true without the `isOverdueFineSettled()` guard added
     * here. Without that guard, this method would return true right after
     * payment, causing the display layer to show a non-zero fine and the
     * runtime backstop (BorrowingOverdueService::flagOne) to immediately
     * re-flag the borrowing to WAITING_PAYMENT — blocking the Return QR.
     *
     * For returned borrowings (return_date set), fines are crystallised in
     * the Fine table, not recalculated here.
     */
    public function isLiveOverdueFineApplicable(Borrowing $borrowing): bool
    {
        if ($borrowing->return_date || $this->overdueDays($borrowing) <= 0) {
            return false;
        }

        return ! $this->isOverdueFineSettled($borrowing);
    }

    /**
     * True if this borrowing's overdue fine has already been paid — same
     * check BorrowingOverdueService::flagOne() uses to prevent re-flagging
     * after payment. Uses the loaded `payments` relation when available
     * (avoids N+1 on list endpoints) and falls back to a DB query otherwise.
     */
    public function isOverdueFineSettled(Borrowing $borrowing): bool
    {
        if ($borrowing->relationLoaded('payments')) {
            return $borrowing->payments
                ->where('purpose', BorrowingPayment::PURPOSE_OVERDUE_FINE)
                ->where('status', BorrowingPayment::PAID)
                ->isNotEmpty();
        }

        return BorrowingPayment::query()
            ->where('borrowing_id', $borrowing->id)
            ->where('purpose', BorrowingPayment::PURPOSE_OVERDUE_FINE)
            ->where('status', BorrowingPayment::PAID)
            ->exists();
    }

    /**
     * Number of items still actually borrowed (not yet returned/lost) —
     * the only items an overdue fine applies to. Uses the already-loaded
     * `items` relation when available (avoids an N+1 across a list of
     * borrowings) and falls back to a direct query otherwise.
     */
    public function activeItemsCount(Borrowing $borrowing): int
    {
        if ($borrowing->relationLoaded('items')) {
            return $borrowing->items->where('status', BorrowingItem::ACTIVE)->count();
        }

        return BorrowingItem::query()
            ->where('borrowing_id', $borrowing->id)
            ->where('status', BorrowingItem::ACTIVE)
            ->count();
    }

    public function normalizeReturnCondition(string $condition): string
    {
        return match ($condition) {
            'damaged' => 'major_damage',
            'minor_damage', 'major_damage', 'lost', 'good' => $condition,
            default => 'good',
        };
    }

    public function damageFeeFor(string $condition, array $feeRules): float
    {
        return match ($condition) {
            'minor_damage' => (float) $feeRules['minor_damage_fee'],
            'major_damage' => (float) $feeRules['major_damage_fee'],
            default => 0.0,
        };
    }

    public function fineNote(int $overdueDays, float $overdueAmount, float $damageAmount, float $lostAmount, string $condition): string
    {
        return sprintf(
            'Denda pengembalian. Kondisi: %s. Keterlambatan: %s hari, denda telat: %.2f, biaya kerusakan: %.2f, biaya hilang: %.2f.',
            $condition,
            $overdueDays,
            $overdueAmount,
            $damageAmount,
            $lostAmount
        );
    }
}
