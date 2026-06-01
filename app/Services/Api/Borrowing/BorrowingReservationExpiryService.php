<?php

namespace App\Services\Api\Borrowing;

use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingQrCode;
use Illuminate\Support\Facades\DB;

class BorrowingReservationExpiryService
{
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

            BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->whereIn('status', ['active', 'refreshed'])
                ->update([
                    'status' => 'expired',
                    'updated_at' => now(),
                ]);

            $borrowing->forceFill([
                'status' => 'expired',
                'updated_at' => now(),
            ])->save();

            return true;
        }, 3);
    }
}