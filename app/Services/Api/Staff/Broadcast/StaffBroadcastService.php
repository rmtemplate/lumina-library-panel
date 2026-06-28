<?php

namespace App\Services\Api\Staff\Broadcast;

use App\Events\Book\BookRegistered;
use App\Events\Borrowing\BorrowSuccess;
use App\Events\Member\MemberRegistered;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single place that announces staff-initiated outcomes over WebSocket —
 * mirrors PickupBroadcastService/PaymentBroadcastService's exact pattern
 * (DB::afterCommit + [WS] logging) for the three NEW staff-module events.
 */
class StaffBroadcastService
{
    public function borrowSuccess(Borrowing $borrowing, string $branchId, int $itemsCount): void
    {
        DB::afterCommit(function () use ($borrowing, $branchId, $itemsCount) {
            BorrowSuccess::dispatch($borrowing->user_id, $branchId, $borrowing->id, $borrowing->borrowing_code, $itemsCount);

            Log::info('[WS] Broadcast sent: borrow.success', [
                'borrowing_id' => $borrowing->id,
                'channel' => ['user.' . $borrowing->user_id, 'branch.' . $branchId],
            ]);
        });
    }

    public function memberRegistered(User $member, string $branchId): void
    {
        DB::afterCommit(function () use ($member, $branchId) {
            MemberRegistered::dispatch($branchId, $member->id, (string) $member->member_code, $member->fullname);

            Log::info('[WS] Broadcast sent: member.registered', [
                'user_id' => $member->id,
                'channel' => 'branch.' . $branchId,
            ]);
        });
    }

    public function bookRegistered(Book $book, string $branchId, int $quantity): void
    {
        DB::afterCommit(function () use ($book, $branchId, $quantity) {
            BookRegistered::dispatch($branchId, $book->id, $book->title, $quantity);

            Log::info('[WS] Broadcast sent: book.registered', [
                'book_id' => $book->id,
                'channel' => 'branch.' . $branchId,
            ]);
        });
    }
}
