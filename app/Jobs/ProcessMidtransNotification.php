<?php

namespace App\Jobs;

use App\Services\Api\Payment\BorrowingPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMidtransNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;
    public int $backoff = 15;
    public int $timeout = 60;

    public function __construct(
        public readonly array $payload
    ) {
    }

    public function handle(BorrowingPaymentService $paymentService): void
    {
        $paymentService->handleNotification($this->payload);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ProcessMidtransNotification job gagal.', [
            'order_id' => $this->payload['order_id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Unique job key to prevent duplicate processing for the same notification.
     */
    public function uniqueId(): string
    {
        return ($this->payload['order_id'] ?? '') . ':' . ($this->payload['transaction_status'] ?? '');
    }
}
