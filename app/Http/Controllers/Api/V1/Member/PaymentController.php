<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\ApiController;
use App\Models\Borrowing;
use App\Models\BorrowingPayment;
use App\Services\Api\Payment\BorrowingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly BorrowingPaymentService $paymentService
    ) {
    }

    /**
     * POST /api/v1/payments/create
     * Creates (or re-creates with a different method) a Core API charge
     * for whatever the member currently owes on a borrowing — see
     * BorrowingPaymentService::resolvePendingCharge() for how "what they
     * owe" is determined (overdue fine, return-condition fee, or pickup
     * no-show penalty).
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'borrowing_id' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::in(config('midtrans.supported_methods', []))],
        ]);

        $user = $request->user();

        $borrowing = Borrowing::query()
            ->where('id', $validated['borrowing_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $borrowing) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
            ]);
        }

        $payment = $this->paymentService->createCorePayment($borrowing, $user, $validated['payment_method']);

        return $this->success(
            data: $this->formatPayment($payment),
            message: 'Pembayaran berhasil dibuat.',
            code: 'PAYMENT_CREATE_SUCCESS'
        );
    }

    /**
     * GET /api/v1/payments/by-borrowing/{borrowing}
     * Lets the payment page recover the current/latest payment for a
     * borrowing on cold start (e.g. member left the app mid-payment and
     * came back) — without this, the page would have no way to know a
     * payment already exists until the member picks a method again.
     * Returns `data: null` (not a 404) if no payment has been created yet.
     */
    public function showByBorrowing(Request $request, string $borrowing): JsonResponse
    {
        $user = $request->user();

        $owned = Borrowing::query()
            ->where('id', $borrowing)
            ->where('user_id', $user->id)
            ->exists();

        if (! $owned) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
            ]);
        }

        $record = BorrowingPayment::query()
            ->where('borrowing_id', $borrowing)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return $this->success(
            data: $record ? $this->formatPayment($record) : null,
            message: $record ? 'Data pembayaran berhasil diambil.' : 'Belum ada pembayaran untuk peminjaman ini.',
            code: $record ? 'PAYMENT_FOUND' : 'PAYMENT_NOT_FOUND'
        );
    }

    /**
     * GET /api/v1/payments/{payment}
     */
    public function show(Request $request, string $payment): JsonResponse
    {
        $record = $this->findOwned($request, $payment);

        return $this->success(
            data: $this->formatPayment($record),
            message: 'Data pembayaran berhasil diambil.',
            code: 'PAYMENT_FOUND'
        );
    }

    /**
     * POST /api/v1/payments/{payment}/refresh
     * Member-facing equivalent of the staff /sync endpoint — lets the
     * "Refresh Status" button in the payment page force a Midtrans check
     * when the webhook hasn't arrived yet.
     */
    public function refresh(Request $request, string $payment): JsonResponse
    {
        $record = $this->findOwned($request, $payment);

        if ($record->isPaid()) {
            return $this->success(
                data: $this->formatPayment($record),
                message: 'Pembayaran sudah dikonfirmasi.',
                code: 'PAYMENT_ALREADY_PAID'
            );
        }

        $updated = $this->paymentService->syncStatusFromMidtrans($record);

        return $this->success(
            data: $this->formatPayment($updated),
            message: $updated->isPaid()
                ? 'Pembayaran berhasil dikonfirmasi dari Midtrans.'
                : 'Status pembayaran diperbarui.',
            code: $updated->isPaid() ? 'PAYMENT_PAID' : 'PAYMENT_STATUS_SYNCED'
        );
    }

    /**
     * GET /api/v1/payments/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $payments = $this->paymentService->history($request->user(), $perPage);

        return $this->paginated(
            paginator: $payments,
            data: $payments->getCollection()->map(fn (BorrowingPayment $p) => $this->formatPayment($p))->values(),
            message: 'Riwayat pembayaran berhasil diambil.',
            code: 'PAYMENT_HISTORY_FOUND'
        );
    }

    private function findOwned(Request $request, string $paymentId): BorrowingPayment
    {
        $record = BorrowingPayment::query()
            ->where('id', $paymentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'payment_id' => ['Data pembayaran tidak ditemukan.'],
            ]);
        }

        return $record;
    }

    private function formatPayment(BorrowingPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'borrowing_id' => $payment->borrowing_id,
            'midtrans_order_id' => $payment->midtrans_order_id,
            'purpose' => $payment->purpose,
            'payment_method' => $payment->payment_method,
            'payment_type' => $payment->payment_type,
            'payment_channel' => $payment->payment_channel,
            'total_amount' => (float) $payment->total_amount,
            'status' => $payment->status,
            'va_number' => $payment->va_number,
            'qr_string' => $payment->qr_string,
            'actions' => $payment->actions,
            'paid_at' => $payment->paid_at?->toISOString(),
            'expired_at' => $payment->expired_at?->toISOString(),
            'is_expired' => $payment->isExpiredOrStale(),
            'created_at' => $payment->created_at?->toISOString(),
        ];
    }
}
