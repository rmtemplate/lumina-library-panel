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
     * GET /api/v1/staff/payments
     * Branch-scoped payments list — feeds the consolidated staff payments
     * page (Pending/Paid/Failed/Overdue/Returned tabs).
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,paid,expired,failed,cancelled'],
            'purpose' => ['nullable', 'in:overdue_fine,return_condition,pickup_no_show'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $payments = $this->paymentService->paginateForBranch(
            branchId: $branchId,
            filters: $validated,
            perPage: (int) ($validated['per_page'] ?? 10)
        );

        return $this->paginated(
            paginator: $payments,
            data: $payments->getCollection()->map(fn ($p) => $this->formatPayment($p, withBorrowing: true))->values(),
            message: 'Daftar pembayaran berhasil diambil.',
            code: 'STAFF_PAYMENT_LIST_SUCCESS'
        );
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

    private function formatPayment(\App\Models\BorrowingPayment $payment, bool $withBorrowing = false): array
    {
        $data = [
            'id' => $payment->id,
            'borrowing_id' => $payment->borrowing_id,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'purpose' => $payment->purpose,
            'payment_method' => $payment->payment_method,
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

        if ($withBorrowing && $payment->relationLoaded('borrowing') && $payment->borrowing) {
            $data['borrowing'] = [
                'id' => $payment->borrowing->id,
                'code' => $payment->borrowing->borrowing_code,
                'status' => $payment->borrowing->status,
                'member' => [
                    'id' => $payment->borrowing->user?->id,
                    'fullname' => $payment->borrowing->user?->fullname,
                ],
            ];
        }

        return $data;
    }
}
