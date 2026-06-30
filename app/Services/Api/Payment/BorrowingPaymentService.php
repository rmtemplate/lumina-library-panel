<?php

namespace App\Services\Api\Payment;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingPayment;
use App\Models\Fine;
use App\Models\PaymentLog;
use App\Models\User;
use App\Services\Api\Borrowing\FineCalculator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class BorrowingPaymentService
{
    public function __construct(
        private readonly MidtransService $midtransService,
        private readonly FineCalculator $fineCalculator,
        private readonly PaymentBroadcastService $paymentBroadcastService
    ) {
    }

    /**
     * Single entry point for member-facing payment creation
     * (POST /api/v1/payments/create). Figures out WHY this borrowing needs
     * payment (purpose) from its current state, computes/collects the
     * right Fine row(s), charges via Midtrans Core API using the chosen
     * method, and persists the result.
     *
     * Idempotent for the SAME method: returns the existing pending charge
     * if one is still valid. Calling with a DIFFERENT method (a "change
     * payment method" request) cancels the old one first.
     */
    public function createCorePayment(Borrowing $borrowing, User $user, string $paymentMethod): BorrowingPayment
    {
        if (! in_array($paymentMethod, config('midtrans.supported_methods', []), true)) {
            throw ValidationException::withMessages([
                'payment_method' => ['Metode pembayaran tidak didukung.'],
            ]);
        }

        return DB::transaction(function () use ($borrowing, $user, $paymentMethod) {
            [$purpose, $fines, $totalAmount] = $this->resolvePendingCharge($borrowing, $user);

            $existing = $this->findActivePendingPayment($borrowing->id);

            if ($existing && $existing->payment_method === $paymentMethod) {
                return $existing;
            }

            if ($existing) {
                $this->cancelExisting($existing, 'Metode pembayaran diganti oleh pengguna.');
            }

            if ($totalAmount <= 0) {
                throw new InvalidArgumentException('Total denda harus lebih dari 0.');
            }

            $orderId = $this->generateOrderId($borrowing);
            $expiryMinutes = (int) config('midtrans.payment_expiry_minutes', 60);
            $itemDetails = $this->buildItemDetails($fines, $borrowing, $purpose);
            $customerDetails = $this->buildCustomerDetails($borrowing, $user);
            $grossAmount = (int) $totalAmount;

            $chargeResponse = $this->charge($paymentMethod, $orderId, $grossAmount, $itemDetails, $customerDetails);

            $payment = BorrowingPayment::create([
                'id' => (string) Str::uuid(),
                'borrowing_id' => $borrowing->id,
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'midtrans_order_id' => $orderId,
                'midtrans_transaction_id' => $chargeResponse['transaction_id'] ?? null,
                'payment_type' => $chargeResponse['payment_type'] ?? $paymentMethod,
                'payment_channel' => $this->resolveChannel($chargeResponse),
                'payment_method' => $paymentMethod,
                'va_number' => $this->resolveVaNumber($chargeResponse),
                'qr_string' => $this->resolveQrString($chargeResponse),
                'actions' => $chargeResponse['actions'] ?? null,
                'status' => BorrowingPayment::PENDING,
                'purpose' => $purpose,
                'expired_at' => now()->addMinutes($expiryMinutes),
                'midtrans_raw' => $chargeResponse,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->logPayment($payment, PaymentLog::SOURCE_CHARGE, $chargeResponse['transaction_status'] ?? null, $chargeResponse);

            Log::info('[PAYMENT] Core API charge created', [
                'payment_id' => $payment->id,
                'borrowing_id' => $borrowing->id,
                'purpose' => $purpose,
                'payment_method' => $paymentMethod,
                'order_id' => $orderId,
                'amount' => $totalAmount,
            ]);

            $this->paymentBroadcastService->created($payment);

            return $payment;
        }, 3);
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

        $this->logPayment($payment, PaymentLog::SOURCE_WEBHOOK, $transactionStatus, $payload);

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

        if ($transactionStatus === 'pending') {
            $payment->forceFill(['midtrans_raw' => $payload, 'updated_at' => now()])->save();
            $this->paymentBroadcastService->pending($payment);

            return;
        }

        if ($transactionStatus === 'expire') {
            DB::transaction(fn () => $this->markExpired($payment, $payload));

            return;
        }

        if (in_array($transactionStatus, ['deny', 'cancel'], true)) {
            DB::transaction(fn () => $this->markCancelled($payment, "Midtrans: {$transactionStatus}", $payload));
        }
    }

    /**
     * Force-check Midtrans for the latest status and apply it.
     * Used as a fallback by staff/member when webhook is late.
     */
    public function syncStatusFromMidtrans(BorrowingPayment $payment): BorrowingPayment
    {
        if ($payment->status === BorrowingPayment::PAID) {
            return $payment;
        }

        $data = $this->midtransService->fetchTransactionStatus($payment->midtrans_order_id);

        $this->logPayment($payment, PaymentLog::SOURCE_SYNC, $data['transaction_status'] ?? null, $data);

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
        } elseif ($transactionStatus === 'expire') {
            DB::transaction(fn () => $this->markExpired($payment, $data));
            $payment->refresh();
        } elseif (in_array($transactionStatus, ['deny', 'cancel'], true)) {
            DB::transaction(fn () => $this->markCancelled($payment, "Midtrans: {$transactionStatus}", $data));
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
     * Find a borrowing's payment for staff/member display.
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

    public function history(User $user, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return BorrowingPayment::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Staff-facing payments list, scoped to their branch via the owning
     * borrowing — used by the consolidated staff payments page
     * (Pending/Paid/Failed/Overdue/Returned tabs map to `status`/`purpose`
     * filters here plus the borrowing's own status, fetched separately).
     */
    public function paginateForBranch(string $branchId, array $filters = [], int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = BorrowingPayment::query()
            ->with(['borrowing.user', 'borrowing.branch', 'borrowing.libraryBranch'])
            ->whereHas('borrowing', fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId));

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }

        return $query->latest()->paginate($perPage);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Determines WHY a borrowing needs payment right now and how much —
     * see docs/borrowing-lifecycle.md for the three purposes.
     *
     * @return array{0: string, 1: Collection<Fine>, 2: float}
     */
    private function resolvePendingCharge(Borrowing $borrowing, User $user): array
    {
        if ($borrowing->status === Borrowing::WAITING_PAYMENT) {
            $existingFines = Fine::query()
                ->where('status', Fine::UNPAID)
                ->whereHas('borrowingItem', fn ($q) => $q->where('borrowing_id', $borrowing->id))
                ->get();

            if ($existingFines->isNotEmpty()) {
                // Damage/lost fees discovered at physical return time —
                // confirmReturn() already created these Fine rows.
                return [
                    BorrowingPayment::PURPOSE_RETURN_CONDITION,
                    $existingFines,
                    $existingFines->sum(fn (Fine $f) => (float) $f->fine_amount),
                ];
            }

            // No item-level fines yet — this borrowing was auto-flagged
            // overdue (BorrowingOverdueService) before any return attempt.
            // Compute the fine fresh and create one Fine row per active item.
            return $this->createFreshOverdueFines($borrowing, $user);
        }

        if ($borrowing->status === Borrowing::EXPIRED) {
            $noShowFine = Fine::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('fine_type', Fine::TYPE_PICKUP_NO_SHOW)
                ->where('status', Fine::UNPAID)
                ->first();

            if ($noShowFine) {
                return [
                    BorrowingPayment::PURPOSE_PICKUP_NO_SHOW,
                    collect([$noShowFine]),
                    (float) $noShowFine->fine_amount,
                ];
            }
        }

        throw ValidationException::withMessages([
            'borrowing_id' => ['Transaksi ini tidak memerlukan pembayaran.'],
        ]);
    }

    private function createFreshOverdueFines(Borrowing $borrowing, User $user): array
    {
        $activeItems = BorrowingItem::query()
            ->where('borrowing_id', $borrowing->id)
            ->where('status', BorrowingItem::ACTIVE)
            ->lockForUpdate()
            ->get();

        if ($activeItems->isEmpty()) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Tidak ada item aktif pada transaksi ini.'],
            ]);
        }

        $overdueDays = $this->fineCalculator->overdueDays($borrowing);
        $finePerDay = $this->fineCalculator->finePerDay();
        $perItemAmount = $overdueDays * $finePerDay;
        $branchId = $borrowing->branch_id ?: $borrowing->library_branch_id;

        $fines = $activeItems->map(function (BorrowingItem $item) use ($borrowing, $user, $branchId, $overdueDays, $perItemAmount) {
            return Fine::query()->updateOrCreate(
                ['borrowing_item_id' => $item->id, 'fine_type' => Fine::TYPE_OVERDUE, 'status' => Fine::UNPAID],
                [
                    'user_id' => $borrowing->user_id,
                    'branch_id' => $branchId,
                    'fine_amount' => $perItemAmount,
                    'overdue_days' => $overdueDays,
                    'notes' => "Denda keterlambatan otomatis: {$borrowing->borrowing_code} ({$overdueDays} hari).",
                    'updated_by' => $user->id,
                ]
            );
        });

        return [
            BorrowingPayment::PURPOSE_OVERDUE_FINE,
            $fines,
            $fines->sum(fn (Fine $f) => (float) $f->fine_amount),
        ];
    }

    private function charge(string $paymentMethod, string $orderId, int $grossAmount, array $itemDetails, array $customerDetails): array
    {
        $vaBank = config("midtrans.va_banks.{$paymentMethod}");

        return match (true) {
            $paymentMethod === 'qris' => $this->midtransService->chargeQris($orderId, $grossAmount, $itemDetails, $customerDetails),
            $paymentMethod === 'gopay' => $this->midtransService->chargeGopay($orderId, $grossAmount, $itemDetails, $customerDetails),
            $paymentMethod === 'shopeepay' => $this->midtransService->chargeShopeepay($orderId, $grossAmount, $itemDetails, $customerDetails),
            $paymentMethod === 'mandiri_bill' => $this->midtransService->chargeEchannel($orderId, $grossAmount, $itemDetails, $customerDetails),
            $vaBank !== null => $this->midtransService->chargeBankTransfer($orderId, $grossAmount, $vaBank, $itemDetails, $customerDetails),
            default => throw new InvalidArgumentException("Metode pembayaran tidak dikenali: {$paymentMethod}"),
        };
    }

    private function cancelExisting(BorrowingPayment $payment, string $reason): void
    {
        $this->midtransService->cancelTransaction($payment->midtrans_order_id);

        $payment->forceFill([
            'status' => BorrowingPayment::CANCELLED,
            'notes' => $reason,
            'updated_at' => now(),
        ])->save();

        $this->logPayment($payment, PaymentLog::SOURCE_CANCEL, 'cancel', ['reason' => $reason]);

        $this->paymentBroadcastService->cancelled($payment, $reason);

        Log::info('[PAYMENT] Superseded payment cancelled', [
            'payment_id' => $payment->id,
            'order_id' => $payment->midtrans_order_id,
            'reason' => $reason,
        ]);
    }

    private function markPaid(BorrowingPayment $payment, array $midtransData): void
    {
        $payment->forceFill([
            'status' => BorrowingPayment::PAID,
            'midtrans_transaction_id' => $midtransData['transaction_id'] ?? $payment->midtrans_transaction_id,
            'payment_type' => $midtransData['payment_type'] ?? $payment->payment_type,
            'payment_channel' => $this->resolveChannel($midtransData) ?? $payment->payment_channel,
            'va_number' => $this->resolveVaNumber($midtransData) ?? $payment->va_number,
            'qr_string' => $this->resolveQrString($midtransData) ?? $payment->qr_string,
            'paid_at' => now(),
            'midtrans_raw' => $midtransData,
            'updated_at' => now(),
        ])->save();

        // Mark every UNPAID fine tied to this borrowing as paid — covers
        // both per-item fines (overdue_fine/return_condition) and the
        // borrowing-level no-show fine (pickup_no_show).
        Fine::query()
            ->where(function ($q) use ($payment) {
                $q->whereHas('borrowingItem', fn ($q2) => $q2->where('borrowing_id', $payment->borrowing_id))
                    ->orWhere('borrowing_id', $payment->borrowing_id);
            })
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

        // What happens to the borrowing row next depends entirely on WHY
        // this payment existed — see docs/borrowing-lifecycle.md.
        $borrowingStatus = match ($payment->purpose) {
            // Pre-return overdue fine: fine settled, member now returns books via QR.
            // READY_FOR_RETURN prevents computeStatus() from re-flagging as overdue
            // (which happened when we used ACTIVE: due_date still < today).
            BorrowingPayment::PURPOSE_OVERDUE_FINE => Borrowing::READY_FOR_RETURN,
            // Damage/lost fee discovered at physical return time: the
            // books are already back — this finalizes the loan.
            BorrowingPayment::PURPOSE_RETURN_CONDITION => Borrowing::RETURNED,
            // No-show penalty: doesn't touch the (already terminal,
            // expired) borrowing row at all — just lifts the user-level
            // checkout block.
            BorrowingPayment::PURPOSE_PICKUP_NO_SHOW => null,
            default => null,
        };

        if ($borrowingStatus !== null) {
            Borrowing::query()
                ->where('id', $payment->borrowing_id)
                ->where('status', Borrowing::WAITING_PAYMENT)
                ->update(['status' => $borrowingStatus, 'updated_at' => now()]);
        }

        Log::info('[PAYMENT] Marked paid', [
            'payment_id' => $payment->id,
            'borrowing_id' => $payment->borrowing_id,
            'purpose' => $payment->purpose,
            'resulting_borrowing_status' => $borrowingStatus,
        ]);

        $this->paymentBroadcastService->success($payment, $borrowingStatus ?? $payment->borrowing?->status ?? '');
    }

    private function markExpired(BorrowingPayment $payment, array $midtransData): void
    {
        $payment->forceFill([
            'status' => BorrowingPayment::EXPIRED,
            'midtrans_raw' => $midtransData,
            'updated_at' => now(),
        ])->save();

        $this->paymentBroadcastService->expired($payment);
    }

    private function markCancelled(BorrowingPayment $payment, string $reason, array $midtransData): void
    {
        $payment->forceFill([
            'status' => BorrowingPayment::CANCELLED,
            'midtrans_raw' => $midtransData,
            'updated_at' => now(),
        ])->save();

        $this->paymentBroadcastService->cancelled($payment, $reason);
    }

    private function logPayment(BorrowingPayment $payment, string $source, ?string $transactionStatus, array $rawPayload): void
    {
        PaymentLog::query()->create([
            'id' => (string) Str::uuid(),
            'borrowing_payment_id' => $payment->id,
            'source' => $source,
            'transaction_status' => $transactionStatus,
            'raw_payload' => $rawPayload,
            'created_at' => now(),
        ]);
    }

    private function buildItemDetails(Collection $fines, Borrowing $borrowing, string $purpose): array
    {
        $label = match ($purpose) {
            BorrowingPayment::PURPOSE_OVERDUE_FINE => 'Denda keterlambatan',
            BorrowingPayment::PURPOSE_PICKUP_NO_SHOW => 'Denda tidak mengambil pesanan',
            default => 'Denda pengembalian',
        };

        return $fines->map(function (Fine $fine) use ($borrowing, $label) {
            $bookTitle = $fine->borrowingItem?->book?->title;

            return [
                'id' => $fine->id,
                'price' => (int) $fine->fine_amount,
                'quantity' => 1,
                'name' => $bookTitle
                    ? "{$label}: {$bookTitle} ({$borrowing->borrowing_code})"
                    : "{$label}: {$borrowing->borrowing_code}",
            ];
        })->values()->all();
    }

    private function buildCustomerDetails(Borrowing $borrowing, User $user): array
    {
        return array_filter([
            'first_name' => $user->fullname ?? 'Member',
            'email' => $user->email,
            'phone' => $user->phone,
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

        if (! empty($data['payment_type']) && in_array($data['payment_type'], ['gopay', 'shopeepay'], true)) {
            return strtoupper((string) $data['payment_type']);
        }

        if (! empty($data['payment_type']) && $data['payment_type'] === 'echannel') {
            return 'MANDIRI_BILL';
        }

        return null;
    }

    private function resolveVaNumber(array $data): ?string
    {
        $vaNumbers = $data['va_numbers'] ?? [];
        if (is_array($vaNumbers) && ! empty($vaNumbers[0]['va_number'])) {
            return (string) $vaNumbers[0]['va_number'];
        }

        // Mandiri echannel returns bill_key/biller_code instead of va_numbers.
        if (! empty($data['bill_key'])) {
            return (string) $data['bill_key'];
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
