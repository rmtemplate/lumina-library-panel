<?php

namespace App\Events\Payment;

use App\Models\BorrowingPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired from BorrowingPaymentService::markPaid() — after the borrowing-row
 * transition for `$payment->purpose` has already been applied (see
 * docs/borrowing-lifecycle.md), so `borrowing_status` here always reflects
 * the POST-payment state the Flutter app should show.
 */
class PaymentSuccess implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $userId,
        public readonly BorrowingPayment $payment,
        public readonly string $borrowingStatus
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'payment.success';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => 'payment_success',
            'payment_id' => $this->payment->id,
            'borrowing_id' => $this->payment->borrowing_id,
            'order_id' => $this->payment->midtrans_order_id,
            'purpose' => $this->payment->purpose,
            'payment_method' => $this->payment->payment_method,
            'payment_channel' => $this->payment->payment_channel,
            'total_amount' => (float) $this->payment->total_amount,
            'paid_at' => $this->payment->paid_at?->toISOString(),
            'borrowing_status' => $this->borrowingStatus,
        ];
    }
}
