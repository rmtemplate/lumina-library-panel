<?php

namespace App\Events\Borrowing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment an ACTIVE borrowing's due_date passes and
 * BorrowingOverdueService flips it to `Borrowing::WAITING_PAYMENT` —
 * automatically, the same instant it becomes overdue, not when staff later
 * attempts a return. See docs/borrowing-lifecycle.md.
 */
class BorrowingOverdue implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly int $overdueDays
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'borrowing.overdue';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'borrowing_overdue',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'waiting_payment',
            'overdue_days' => $this->overdueDays,
        ];
    }
}
