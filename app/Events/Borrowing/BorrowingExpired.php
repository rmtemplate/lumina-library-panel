<?php

namespace App\Events\Borrowing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an awaiting_pickup borrowing's reservation deadline passes and
 * BorrowingReservationExpiryService::expireOne() flips it to `expired`
 * (whether triggered by the scheduler, the runtime read-path backstop, or a
 * scan/refresh attempt that discovers it's too late). Lets "My Books" and
 * any open borrowing-detail screen update to "Expired" instantly instead of
 * only on the next manual refresh — see docs/scheduler.md for the expiry
 * mechanism itself.
 */
class BorrowingExpired implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'borrowing.expired';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'borrowing_expired',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'expired',
        ];
    }
}
