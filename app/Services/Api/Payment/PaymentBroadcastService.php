<?php

namespace App\Services\Api\Payment;

use App\Events\Payment\PaymentCancelled;
use App\Events\Payment\PaymentCreated;
use App\Events\Payment\PaymentExpired;
use App\Events\Payment\PaymentPending;
use App\Events\Payment\PaymentSuccess;
use App\Models\BorrowingPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single place that knows how to announce a payment-lifecycle event over
 * WebSocket — mirrors App\Services\Api\Pickup\PickupBroadcastService.
 * Used by BorrowingPaymentService so the [WS] logging and DB::afterCommit
 * wrapping isn't repeated at every call site.
 */
class PaymentBroadcastService
{
    public function created(BorrowingPayment $payment): void
    {
        DB::afterCommit(function () use ($payment) {
            PaymentCreated::dispatch($payment->user_id, $payment);
            $this->logSent('payment.created', $payment->id, $payment->user_id);
        });
    }

    public function pending(BorrowingPayment $payment): void
    {
        DB::afterCommit(function () use ($payment) {
            PaymentPending::dispatch($payment->user_id, $payment->id, $payment->borrowing_id, $payment->midtrans_order_id);
            $this->logSent('payment.pending', $payment->id, $payment->user_id);
        });
    }

    public function success(BorrowingPayment $payment, string $borrowingStatus): void
    {
        DB::afterCommit(function () use ($payment, $borrowingStatus) {
            PaymentSuccess::dispatch($payment->user_id, $payment, $borrowingStatus);
            $this->logSent('payment.success', $payment->id, $payment->user_id);
        });
    }

    public function expired(BorrowingPayment $payment): void
    {
        DB::afterCommit(function () use ($payment) {
            PaymentExpired::dispatch($payment->user_id, $payment->id, $payment->borrowing_id, $payment->midtrans_order_id);
            $this->logSent('payment.expired', $payment->id, $payment->user_id);
        });
    }

    public function cancelled(BorrowingPayment $payment, string $reason): void
    {
        DB::afterCommit(function () use ($payment, $reason) {
            PaymentCancelled::dispatch($payment->user_id, $payment->id, $payment->borrowing_id, $payment->midtrans_order_id, $reason);
            $this->logSent('payment.cancelled', $payment->id, $payment->user_id);
        });
    }

    private function logSent(string $event, string $paymentId, string $userId): void
    {
        Log::info("[WS] Broadcast sent: {$event}", [
            'payment_id' => $paymentId,
            'channel' => 'user.' . $userId,
        ]);
    }
}
