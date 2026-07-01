<?php

namespace App\Events\Return;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired from StaffQrScanService::confirmReturn() once staff finishes verifying
 * the physical books and finalizes the charges. Carries the full charge
 * breakdown so the member's app can, WITHOUT a manual refresh, refetch the
 * borrowing and — when payment is required — auto-open the payment bottom
 * sheet showing the outstanding total. When [paymentRequired] is false the
 * loan is already closed (RETURNED) and no payment section is shown.
 */
class ReturnVerified implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array{overdue: float, damage: float, lost: float, manual: float, total: float, items: array}  $charges
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $borrowingId,
        public readonly string $borrowingCode,
        public readonly bool $paymentRequired,
        public readonly array $charges
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'return.verified';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'return_verified',
            'borrowing_id' => $this->borrowingId,
            'borrowing_code' => $this->borrowingCode,
            'payment_required' => $this->paymentRequired,
            'status' => $this->paymentRequired ? 'waiting_payment' : 'returned',
            'charges' => $this->charges,
        ];
    }
}
