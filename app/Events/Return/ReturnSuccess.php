<?php

namespace App\Events\Return;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired from StaffQrScanService::confirmReturn() specifically on the
 * no-fine-due path (status -> RETURNED immediately). When damage/lost fees
 * are owed instead, the existing MyBookStatusUpdated('borrowing_waiting_payment')
 * broadcast still fires (unchanged) — that case isn't "return success" yet,
 * since the member still owes money before the loan is fully closed.
 */
class ReturnSuccess implements ShouldBroadcastNow
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
        return 'return.success';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'return_success',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'status' => 'returned',
        ];
    }
}
