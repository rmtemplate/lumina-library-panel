<?php

namespace App\Events\Staff;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Audit/visibility event fired alongside PickupSuccess specifically when a
 * pickup bypassed real QR verification (staff bypass or local dev bypass —
 * see docs/development-bypass.md). Unlike PickupSuccess (member-facing,
 * `user.{userId}`), this broadcasts on `borrowing.{borrowingId}` — which
 * staff on the same branch are authorized to read (routes/channels.php) —
 * so a staff dashboard can show "pickup confirmed without a QR scan" live,
 * distinct from the member's identical-looking success notification.
 */
class StaffForcePickup implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly string $pickupMethod,
        public readonly ?string $actorId,
        public readonly string $actorLabel
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('borrowing.' . $this->borrowingId)];
    }

    public function broadcastAs(): string
    {
        return 'staff.force-pickup';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'staff_force_pickup',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'pickup_method' => $this->pickupMethod,
            'actor_id' => $this->actorId,
            'actor_label' => $this->actorLabel,
        ];
    }
}
