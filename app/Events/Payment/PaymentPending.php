<?php

namespace App\Events\Payment;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a Midtrans webhook reports `transaction_status: pending`
 * (e.g. a VA was generated and is awaiting transfer, or a GoPay/QRIS
 * payment is awaiting user confirmation in their wallet app).
 */
class PaymentPending implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly string $paymentId,
        public readonly string $borrowingId,
        public readonly string $orderId
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'payment.pending';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'payment_pending',
            'payment_id' => $this->paymentId,
            'borrowing_id' => $this->borrowingId,
            'order_id' => $this->orderId,
            'status' => 'pending',
        ];
    }
}
