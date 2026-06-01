<?php

namespace App\Services\Api\Payment;

use App\Events\BorrowingPaymentUpdated;
use App\Models\Borrowing;
use App\Models\BorrowingPayment;
use App\Models\Fine;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class BorrowingPaymentService
{
    public function __construct(
        private readonly MidtransService $midtransService
    ) {
    }

    /**
     * Create a Midtrans payment for a borrowing's outstanding fines.
     * Idempotent: returns existing pending payment if still valid.
     */
    public function createForBorrowing(
        Borrowing $borrowing,
        Collection $fines,
        User $staff
    ): BorrowingPayment {
        $existing = $this->findActivePendingPayment($borrowing->id);

        if ($existing) {
            return $existing;
        }

        $totalAmount = $fines->sum(fn (Fine $f) => (float) $f->fine_amount);

        if ($totalAmount <= 0) {
            throw new InvalidArgumentException('Total denda harus lebih dari 0.');
        }

        $orderId = $this->generateOrderId($borrowing);
        $expiryMinutes = (int) config('midtrans.payment_expiry_minutes', 60);

        $snapResponse = $this->midtransService->createSnapToken([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $totalAmount,
            ],
            'item_details' => $this->buildItemDetails($fines, $borrowing),
            'customer_details' => $this->buildCustomerDetails($borrowing),
            'enabled_payments' => config('midtrans.enabled_payments', ['qris', 'bank_transfer']),
            'expiry' => [
                'unit' => 'minutes',
                'duration' => $expiryMinutes,
            ],
            'callbacks' => array_filter([
                'notification' => config('midtrans.notification_url'),
                'finish' => config('midtrans.finish_redirect_url'),
            ]),
        ]);

        return BorrowingPayment::create([
            'borrowing_id' => $borrowing->id,
            'user_id' => $borrowing->user_id,
            'total_amount' => $totalAmount,
            'midtrans_order_id' => $orderId,
            'snap_token' => $snapResponse['token'],
            'payment_url' => $snapResponse['redirect_url'],
            'status' => BorrowingPayment::PENDING,
            'expired_at' => now()->addMinutes($expiryMinutes),
            'created_by' => $staff->id,
            'updated_by' => $staff->id,
        ]);
    }

    /**
     * Process Midtrans notification webhook payload.
     * Called from the queued job. Contains full idempotency protection.
     */
    public function handleNotification(array $payload): void
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');

        if (! $this->midtransService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans: signature tidak valid.', ['order_id' => $orderId]);
            throw new InvalidArgumentException('Signature Midtrans tidak valid.');
        }

        $payment = BorrowingPayment::where('midtrans_order_id', $orderId)
            ->lockForUpdate()
            ->first();

        if (! $payment) {
            Log::warning('Midtrans: order tidak ditemukan.', ['order_id' => $orderId]);

            return;
        }

        if ($payment->status === BorrowingPayment::PAID) {
            return;
        }

        $isPaid = match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept',
            'settlement' => true,
            default => false,
        };

        if ($isPaid) {
            DB::transaction(fn () => $this->markPaid($payment, $payload));

            return;
        }

        if (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
            DB::transaction(fn () => $this->markFailed($payment, $transactionStatus, $payload));
        }
    }

    /**
     * Force-check Midtrans for the latest status and apply it.
     * Used as a fallback by staff when webhook is late.
     */
    public function syncStatusFromMidtrans(BorrowingPayment $payment): BorrowingPayment
    {
        if ($payment->status === BorrowingPayment::PAID) {
            return $payment;
        }

        $data = $this->midtransService->fetchTransactionStatus($payment->midtrans_order_id);

        if (empty($data['transaction_status'])) {
            return $payment;
        }

        $fraudStatus = $data['fraud_status'] ?? '';
        $transactionStatus = $data['transaction_status'];

        $isPaid = match ($transactionStatus) {
            'capture' => $fraudStatus === 'accept',
            'settlement' => true,
            default => false,
        };

        if ($isPaid && $payment->status !== BorrowingPayment::PAID) {
            DB::transaction(fn () => $this->markPaid($payment, $data));
            $payment->refresh();
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
            DB::transaction(fn () => $this->markFailed($payment, $transactionStatus, $data));
            $payment->refresh();
        }

        return $payment;
    }

    /**
     * Find an active (pending, not yet expired) payment for a borrowing.
     */
    public function findActivePendingPayment(string $borrowingId): ?BorrowingPayment
    {
        return BorrowingPayment::query()
            ->where('borrowing_id', $borrowingId)
            ->where('status', BorrowingPayment::PENDING)
            ->where('expired_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Find a borrowing's payment for staff display.
     * Throws if not found.
     */
    public function findForBorrowing(string $borrowingId): BorrowingPayment
    {
        $payment = BorrowingPayment::query()
            ->where('borrowing_id', $borrowingId)
            ->latest()
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Tidak ada data pembayaran untuk peminjaman ini.'],
            ]);
        }

        return $payment;
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function markPaid(BorrowingPayment $payment, array $midtransData): void
    {
        $payment->forceFill([
            'status' => BorrowingPayment::PAID,
            'midtrans_transaction_id' => $midtransData['transaction_id'] ?? null,
            'payment_type' => $midtransData['payment_type'] ?? null,
            'payment_channel' => $this->resolveChannel($midtransData),
            'va_number' => $this->resolveVaNumber($midtransData),
            'qr_string' => $this->resolveQrString($midtransData),
            'paid_at' => now(),
            'midtrans_raw' => $midtransData,
            'updated_at' => now(),
        ])->save();

        // Mark all unpaid fines for this borrowing as paid.
        Fine::query()
            ->whereHas('borrowingItem', fn ($q) => $q->where('borrowing_id', $payment->borrowing_id))
            ->where('status', Fine::UNPAID)
            ->get()
            ->each(function (Fine $fine) {
                $fine->forceFill([
                    'status' => Fine::PAID,
                    'paid_at' => now(),
                    'paid_amount' => $fine->fine_amount,
                    'updated_at' => now(),
                ])->save();
            });

        // Close the borrowing.
        Borrowing::query()
            ->where('id', $payment->borrowing_id)
            ->where('status', Borrowing::WAITING_PAYMENT)
            ->update([
                'status' => Borrowing::RETURNED,
                'updated_at' => now(),
            ]);

        DB::afterCommit(function () use ($payment) {
            broadcast(new BorrowingPaymentUpdated(
                borrowingId: $payment->borrowing_id,
                userId: $payment->user_id,
                payload: [
                    'event' => 'payment_paid',
                    'borrowing_id' => $payment->borrowing_id,
                    'payment_id' => $payment->id,
                    'payment_type' => $payment->payment_type,
                    'payment_channel' => $payment->payment_channel,
                    'status' => BorrowingPayment::PAID,
                    'borrowing_status' => Borrowing::RETURNED,
                    'paid_at' => $payment->paid_at?->toISOString(),
                ]
            ));
        });
    }

    private function markFailed(BorrowingPayment $payment, string $reason, array $midtransData): void
    {
        $status = $reason === 'expire' ? BorrowingPayment::EXPIRED : BorrowingPayment::FAILED;

        $payment->forceFill([
            'status' => $status,
            'midtrans_raw' => $midtransData,
            'updated_at' => now(),
        ])->save();

        DB::afterCommit(function () use ($payment, $status, $reason) {
            broadcast(new BorrowingPaymentUpdated(
                borrowingId: $payment->borrowing_id,
                userId: $payment->user_id,
                payload: [
                    'event' => 'payment_' . $reason,
                    'borrowing_id' => $payment->borrowing_id,
                    'payment_id' => $payment->id,
                    'status' => $status,
                    'borrowing_status' => Borrowing::WAITING_PAYMENT,
                ]
            ));
        });
    }

    private function buildItemDetails(Collection $fines, Borrowing $borrowing): array
    {
        return $fines->map(function (Fine $fine) use ($borrowing) {
            $bookTitle = $fine->borrowingItem?->book?->title ?? 'Buku';

            return [
                'id' => $fine->id,
                'price' => (int) $fine->fine_amount,
                'quantity' => 1,
                'name' => "Denda: {$bookTitle} ({$borrowing->borrowing_code})",
            ];
        })->values()->all();
    }

    private function buildCustomerDetails(Borrowing $borrowing): array
    {
        $user = $borrowing->relationLoaded('user') ? $borrowing->user : $borrowing->user()->first();

        return array_filter([
            'first_name' => $user?->fullname ?? 'Member',
            'email' => $user?->email,
            'phone' => $user?->phone,
        ]);
    }

    private function generateOrderId(Borrowing $borrowing): string
    {
        $code = strtoupper(str_replace(['-', ' '], '', $borrowing->borrowing_code));

        return 'LUMINAPAY-' . $code . '-' . now()->timestamp;
    }

    private function resolveChannel(array $data): ?string
    {
        if (! empty($data['bank'])) {
            return strtoupper((string) $data['bank']);
        }

        if (! empty($data['payment_type']) && $data['payment_type'] === 'qris') {
            return 'QRIS';
        }

        return null;
    }

    private function resolveVaNumber(array $data): ?string
    {
        $vaNumbers = $data['va_numbers'] ?? [];
        if (is_array($vaNumbers) && ! empty($vaNumbers[0]['va_number'])) {
            return (string) $vaNumbers[0]['va_number'];
        }

        return null;
    }

    private function resolveQrString(array $data): ?string
    {
        if (! empty($data['qr_string'])) {
            return (string) $data['qr_string'];
        }

        // Gopay / ShopeePay action URL as fallback
        $actions = $data['actions'] ?? [];
        foreach ($actions as $action) {
            if (in_array($action['name'] ?? '', ['generate-qr-code', 'deeplink-redirect'], true)) {
                return $action['url'] ?? null;
            }
        }

        return null;
    }
}
