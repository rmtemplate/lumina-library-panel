<?php

namespace App\Events\Pickup;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * NOT CURRENTLY WIRED TO ANY ENDPOINT.
 *
 * There is no borrowing-cancellation feature anywhere in this codebase
 * today (no route, no service method sets Borrowing::CANCELLED). This
 * class exists so the event/channel/payload shape is ready the moment such
 * a feature is built — dispatch it from wherever a borrowing transitions to
 * `cancelled` — but introducing the cancellation feature itself was out of
 * scope for the realtime/WebSocket setup this class was added for.
 */
class PickupCancelled implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly ?string $reason = null
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'pickup.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'pickup_cancelled',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'cancelled',
            'reason' => $this->reason,
        ];
    }
}
