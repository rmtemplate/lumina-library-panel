<?php

namespace App\Events\Pickup;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a pickup attempt is rejected by business validation (invalid/
 * expired QR, reservation already expired, items no longer reserved, etc).
 *
 * Broadcast on the `borrowing.{borrowingId}` channel (authorized for both
 * the owning member AND staff on the same branch — see routes/channels.php)
 * rather than the member's `user.{userId}` channel: this is primarily
 * useful for a staff-facing screen to see "this QR just failed" live while
 * a member is standing at the counter, and the channel already existed
 * with no event using it.
 */
class PickupFailed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $borrowingId,
        public readonly ?string $borrowingCode,
        public readonly string $reason,
        public readonly string $attemptedVia
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('borrowing.' . $this->borrowingId)];
    }

    public function broadcastAs(): string
    {
        return 'pickup.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'pickup_failed',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'reason' => $this->reason,
            'attempted_via' => $this->attemptedVia,
        ];
    }
}
