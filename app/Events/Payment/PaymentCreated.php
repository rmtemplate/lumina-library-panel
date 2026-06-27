<?php

namespace App\Events\Payment;

use App\Models\BorrowingPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment a Core API charge is created (BorrowingPaymentService::
 * createCorePayment) — lets the Flutter payment page swap from "creating
 * payment..." straight to the VA/QRIS details without polling.
 */
class PaymentCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly BorrowingPayment $payment
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'payment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'payment_created',
            'payment_id' => $this->payment->id,
            'borrowing_id' => $this->payment->borrowing_id,
            'order_id' => $this->payment->midtrans_order_id,
            'purpose' => $this->payment->purpose,
            'payment_method' => $this->payment->payment_method,
            'status' => $this->payment->status,
            'total_amount' => (float) $this->payment->total_amount,
            'expired_at' => $this->payment->expired_at?->toISOString(),
        ];
    }
}
