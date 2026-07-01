<?php

namespace App\Events\Return;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired from StaffQrScanService::previewReturn() the moment staff scans the
 * member's Return QR. Tells the member's app to disable the Return QR and
 * show "waiting for staff verification" — the member cannot pay yet; that
 * only becomes possible once ReturnVerified fires after the staff confirms.
 */
class ReturnPendingVerification implements ShouldBroadcastNow
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
        return 'return.pending_verification';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'return_pending_verification',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'return_pending_verification',
        ];
    }
}
