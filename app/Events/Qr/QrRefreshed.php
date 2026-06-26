<?php

namespace App\Events\Qr;

use App\Models\BorrowingQrCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever an existing QR is replaced by a new one (member tapped
 * "Perbarui QR", or the previous QR's TTL forced a fresh request). The
 * Flutter QR bottom sheet should treat this exactly like a successful
 * refresh response — swap in the new image/expiry — even if it arrived
 * while a different screen was open.
 */
class QrRefreshed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly BorrowingQrCode $qr,
        public readonly ?string $previousQrId = null
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'qr.refreshed';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'qr_refreshed',
            'qr_id' => $this->qr->id,
            'qr_code' => $this->qr->qr_code,
            'qr_type' => $this->qr->qr_type,
            'borrowing_id' => $this->qr->borrowing_id,
            'status' => $this->qr->status,
            'expires_at' => $this->qr->expires_at?->toISOString(),
            'refresh_count' => (int) $this->qr->refresh_count,
            'previous_qr_id' => $this->previousQrId,
        ];
    }
}
