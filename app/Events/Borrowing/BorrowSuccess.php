<?php

namespace App\Events\Borrowing;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when staff directly create+activate a borrowing for a walk-in
 * member (the Borrow Cart flow — StaffBorrowCartService::confirm()), as
 * opposed to a member self-checking-out then later picking up via QR
 * (which fires Pickup\PickupSuccess instead). Broadcast on both the
 * member's own channel (so their My Books list updates instantly) and the
 * branch channel (so every staff dashboard at that branch refreshes).
 */
class BorrowSuccess implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $branchId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly int $itemsCount
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
            new PrivateChannel('branch.' . $this->branchId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'borrow.success';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'borrow_success',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'active',
            'items_count' => $this->itemsCount,
        ];
    }
}
