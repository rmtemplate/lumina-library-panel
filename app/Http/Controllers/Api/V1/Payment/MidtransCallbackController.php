<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMidtransNotification;
use App\Models\BorrowingPayment;
use App\Services\Api\Payment\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtransService
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');

        Log::info('Midtrans notification diterima.', [
            'order_id' => $orderId,
            'transaction_status' => $payload['transaction_status'] ?? null,
            'payment_type' => $payload['payment_type'] ?? null,
        ]);

        // Fast-path signature check before dispatching the job.
        $signatureOk = $this->midtransService->verifySignature(
            orderId: $orderId,
            statusCode: (string) ($payload['status_code'] ?? ''),
            grossAmount: (string) ($payload['gross_amount'] ?? ''),
            signatureKey: (string) ($payload['signature_key'] ?? ''),
        );

        if (! $signatureOk) {
            Log::warning('Midtrans: signature tidak valid, request ditolak.', ['order_id' => $orderId]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        // Quick idempotency check: skip if already paid.
        $alreadyPaid = BorrowingPayment::where('midtrans_order_id', $orderId)
            ->where('status', BorrowingPayment::PAID)
            ->exists();

        if ($alreadyPaid) {
            return response()->json(['message' => 'OK']);
        }

        // Dispatch to queue so Midtrans gets a fast 200 response.
        ProcessMidtransNotification::dispatch($payload);

        return response()->json(['message' => 'OK']);
    }
}
