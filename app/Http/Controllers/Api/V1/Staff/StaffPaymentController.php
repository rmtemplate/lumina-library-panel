<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Models\Borrowing;
use App\Services\Api\Payment\BorrowingPaymentService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffPaymentController extends ApiController
{
    public function __construct(
        private readonly BorrowingPaymentService $paymentService,
        private readonly StaffAccessService $staffAccessService
    ) {
    }

    /**
     * GET /api/v1/staff/borrowings/{borrowing}/payment
     * Show the latest payment record for a borrowing.
     */
    public function show(Request $request, string $borrowingId): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $borrowing = Borrowing::query()
            ->where('id', $borrowingId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->firstOrFail();

        $payment = $this->paymentService->findForBorrowing($borrowing->id);

        return $this->success(
            data: $this->formatPayment($payment),
            message: 'Data pembayaran berhasil diambil.',
            code: 'PAYMENT_FOUND'
        );
    }

    /**
     * POST /api/v1/staff/borrowings/{borrowing}/payment/sync
     * Force-check payment status from Midtrans.
     * Use when webhook has not arrived and staff needs to confirm payment manually.
     */
    public function sync(Request $request, string $borrowingId): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $borrowing = Borrowing::query()
            ->where('id', $borrowingId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->firstOrFail();

        $payment = $this->paymentService->findForBorrowing($borrowing->id);

        if ($payment->isPaid()) {
            return $this->success(
                data: $this->formatPayment($payment),
                message: 'Pembayaran sudah dikonfirmasi.',
                code: 'PAYMENT_ALREADY_PAID'
            );
        }

        $updated = $this->paymentService->syncStatusFromMidtrans($payment);

        return $this->success(
            data: $this->formatPayment($updated),
            message: $updated->isPaid()
                ? 'Pembayaran berhasil dikonfirmasi dari Midtrans.'
                : 'Status pembayaran diperbarui.',
            code: $updated->isPaid() ? 'PAYMENT_PAID' : 'PAYMENT_STATUS_SYNCED'
        );
    }

    private function formatPayment(\App\Models\BorrowingPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'borrowing_id' => $payment->borrowing_id,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'total_amount' => (float) $payment->total_amount,
            'status' => $payment->status,
            'payment_type' => $payment->payment_type,
            'payment_channel' => $payment->payment_channel,
            'va_number' => $payment->va_number,
            'qr_string' => $payment->qr_string,
            'snap_token' => $payment->snap_token,
            'payment_url' => $payment->payment_url,
            'paid_at' => $payment->paid_at?->toISOString(),
            'expired_at' => $payment->expired_at?->toISOString(),
            'is_expired' => $payment->isExpiredOrStale(),
            'created_at' => $payment->created_at?->toISOString(),
        ];
    }
}
