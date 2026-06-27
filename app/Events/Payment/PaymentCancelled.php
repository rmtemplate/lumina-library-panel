<?php

namespace App\Events\Payment;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when either Midtrans reports `transaction_status: cancel`/`deny`,
 * OR the member picked a different payment method (BorrowingPaymentService
 * cancels the superseded pending charge before creating the new one — see
 * docs/payment-flow.md "Change Payment Method").
 */
class PaymentCancelled implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $paymentId,
        public readonly string $borrowingId,
        public readonly string $orderId,
        public readonly string $reason
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'payment.cancelled';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'payment_cancelled',
            'payment_id' => $this->paymentId,
            'borrowing_id' => $this->borrowingId,
            'order_id' => $this->orderId,
            'status' => 'cancelled',
            'reason' => $this->reason,
        ];
    }
}
