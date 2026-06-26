<?php

namespace App\Events\Qr;

use App\Models\BorrowingQrCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the FIRST time a QR is created for a borrowing (at checkout for a
 * pickup QR, or the first return-QR request for that borrowing). Subsequent
 * regenerations fire QrRefreshed instead — see QrRefreshed.
 *
 * Uses ShouldBroadcastNow (synchronous), not the queued ShouldBroadcast,
 * matching this app's existing MyBookStatusUpdated/BorrowingPaymentUpdated
 * convention — there is no queue worker running by default in this local
 * dev setup (QUEUE_CONNECTION=database, nobody runs `queue:work`), so a
 * queued event would silently never broadcast. See docs/backend-broadcasting.md
 * for how to switch to queued ShouldBroadcast in production if desired.
 */
class QrGenerated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly BorrowingQrCode $qr
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'qr.generated';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'qr_generated',
            'qr_id' => $this->qr->id,
            'qr_code' => $this->qr->qr_code,
            'qr_type' => $this->qr->qr_type,
            'borrowing_id' => $this->qr->borrowing_id,
            'status' => $this->qr->status,
            'expires_at' => $this->qr->expires_at?->toISOString(),
            'refresh_count' => (int) $this->qr->refresh_count,
        ];
    }
}
