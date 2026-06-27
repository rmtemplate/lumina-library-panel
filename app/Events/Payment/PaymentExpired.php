<?php

namespace App\Events\Payment;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a Midtrans webhook reports `transaction_status: expire` — the
 * VA/QRIS/wallet deeplink's payment window closed unpaid. The borrowing
 * stays WAITING_PAYMENT; the member must create a new charge (possibly
 * with a different method) to try again.
 */
class PaymentExpired implements ShouldBroadcastNow
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
        return 'payment.expired';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'payment_expired',
            'payment_id' => $this->paymentId,
            'borrowing_id' => $this->borrowingId,
            'order_id' => $this->orderId,
            'status' => 'expired',
        ];
    }
}
