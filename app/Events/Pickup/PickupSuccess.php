<?php

namespace App\Events\Pickup;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a pickup completes successfully, regardless of how it was
 * confirmed — a real QR scan (StaffQrScanService::scanPickup), the staff
 * bypass (StaffQrScanService::forcePickup), or the unauthenticated local
 * dev bypass (App\Services\Api\Dev\DevBypassService::forcePickup). The
 * payload is intentionally identical across all three sources — the
 * Flutter app has no way to distinguish a real scan from a bypass, by
 * design (see docs/development-bypass.md).
 */
class PickupSuccess implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly string $pickupMethod,
        public readonly string $pickedAt
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'pickup.success';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'pickup_success',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'active',
            'pickup_method' => $this->pickupMethod,
            'picked_at' => $this->pickedAt,
        ];
    }
}
