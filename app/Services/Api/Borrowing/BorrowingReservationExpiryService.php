<?php

namespace App\Services\Api\Borrowing;

use App\Events\Borrowing\BorrowingExpired;
use App\Events\Qr\QrExpired;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingQrCode;
use App\Models\Fine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BorrowingReservationExpiryService
{
    public function __construct(
        private readonly FineCalculator $fineCalculator
    ) {
    }

    public function expireDueReservations(int $limit = 100): int
    {
        $borrowingIds = Borrowing::query()
            ->where('status', 'awaiting_pickup')
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())
            ->limit($limit)
            ->pluck('id');

        $expiredCount = 0;

        foreach ($borrowingIds as $borrowingId) {
            if ($this->expireOne((string) $borrowingId)) {
                $expiredCount++;
            }
        }

        if ($expiredCount > 0) {
            Log::info('[QR][EXPIRE] Bulk borrowing reservation expiry completed', [
                'expired_count' => $expiredCount,
                'limit' => $limit,
            ]);
        }

        return $expiredCount;
    }

    public function expireOne(string $borrowingId): bool
    {
        return DB::transaction(function () use ($borrowingId) {
            $borrowing = Borrowing::query()
                ->where('id', $borrowingId)
                ->lockForUpdate()
                ->first();

            if (! $borrowing) {
                return false;
            }

            if ($borrowing->status !== 'awaiting_pickup') {
                return false;
            }

            if (! $borrowing->reservation_expires_at || $borrowing->reservation_expires_at->isFuture()) {
                return false;
            }

            $borrowingItems = BorrowingItem::query()
                ->where('borrowing_id', $borrowing->id)
                ->lockForUpdate()
                ->get();

            $bookItemIds = $borrowingItems
                ->pluck('book_item_id')
                ->filter()
                ->unique()
                ->values();

            if ($bookItemIds->isNotEmpty()) {
                BookItem::query()
                    ->whereIn('id', $bookItemIds)
                    ->where('status', BookItem::RESERVED)
                    ->lockForUpdate()
                    ->update([
                        'status' => BookItem::AVAILABLE,
                        'updated_at' => now(),
                    ]);
            }

            BorrowingItem::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now(),
                ]);

            $expiredQrIds = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->whereIn('status', ['active', 'refreshed'])
                ->pluck('id');

            if ($expiredQrIds->isNotEmpty()) {
                BorrowingQrCode::query()
                    ->whereIn('id', $expiredQrIds)
                    ->update([
                        'status' => 'expired',
                        'updated_at' => now(),
                    ]);
            }

            $borrowing->forceFill([
                'status' => 'expired',
                'updated_at' => now(),
            ])->save();

            // No-show penalty fee — a flat, one-time amount (not accruing
            // like an overdue fine), so it's safe to create it right now
            // rather than computing it fresh later. Paying it lifts a
            // user-level checkout block (see CheckoutService::checkout())
            // — it does NOT resurrect this borrowing or its reserved items,
            // which may already be claimed by someone else by the time it's
            // paid. See docs/borrowing-lifecycle.md.
            $noShowFee = $this->fineCalculator->pickupNoShowFee();

            if ($noShowFee > 0) {
                Fine::query()->create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $borrowing->user_id,
                    'branch_id' => $borrowing->branch_id ?: $borrowing->library_branch_id,
                    'borrowing_id' => $borrowing->id,
                    'borrowing_item_id' => null,
                    'fine_amount' => $noShowFee,
                    'overdue_days' => 0,
                    'status' => Fine::UNPAID,
                    'fine_type' => Fine::TYPE_PICKUP_NO_SHOW,
                    'notes' => "Denda tidak mengambil pesanan: {$borrowing->borrowing_code}.",
                ]);
            }

            Log::info('[QR][EXPIRE] Borrowing auto-expired', [
                'borrowing_id' => $borrowing->id,
                'borrowing_code' => $borrowing->borrowing_code,
                'reservation_expires_at' => $borrowing->reservation_expires_at?->toISOString(),
                'no_show_fee' => $noShowFee,
            ]);

            DB::afterCommit(function () use ($borrowing, $expiredQrIds) {
                BorrowingExpired::dispatch($borrowing->user_id, $borrowing->id, $borrowing->borrowing_code);
                Log::info('[WS] Broadcast sent: borrowing.expired', ['borrowing_id' => $borrowing->id, 'channel' => 'user.' . $borrowing->user_id]);

                if ($expiredQrIds->isNotEmpty()) {
                    QrExpired::dispatch($borrowing->user_id, $borrowing->id, $borrowing->borrowing_code, $expiredQrIds->all());
                    Log::info('[WS] Broadcast sent: qr.expired', ['borrowing_id' => $borrowing->id, 'qr_ids' => $expiredQrIds->all(), 'channel' => 'user.' . $borrowing->user_id]);
                }
            });

            return true;
        }, 3);
    }
}