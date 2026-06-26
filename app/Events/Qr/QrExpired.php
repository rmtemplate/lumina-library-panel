<?php

namespace App\Events\Qr;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a borrowing's QR(s) are force-expired as a side effect of the
 * borrowing itself expiring (see BorrowingReservationExpiryService::expireOne).
 * Lets the Flutter QR bottom sheet immediately show "expired" instead of
 * waiting for its local TTL countdown to reach zero or for the user to
 * manually reopen the sheet.
 */
class QrExpired implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly array $qrIds
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'qr.expired';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'qr_expired',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'qr_ids' => $this->qrIds,
        ];
    }
}
