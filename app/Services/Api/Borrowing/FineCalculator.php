<?php

namespace App\Services\Api\Borrowing;

use App\Models\Borrowing;
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
     * Aggregate overdue fine for a still-borrowed (not yet returned)
     * borrowing — used by the automatic overdue-payment flow, where there's
     * no per-returned-item granularity yet (nothing has been returned).
     */
    public function overdueFineAmount(Borrowing $borrowing, int $activeItemsCount): float
    {
        return $this->overdueDays($borrowing) * $this->finePerDay() * max($activeItemsCount, 1);
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
