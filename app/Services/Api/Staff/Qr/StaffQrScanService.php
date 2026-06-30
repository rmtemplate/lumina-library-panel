<?php

namespace App\Services\Api\Staff\Qr;

use App\Enums\QrCodeType;
use App\Events\MyBookStatusUpdated;
use App\Http\Resources\Api\Staff\StaffBookScanResource;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingQrCode;
use App\Models\Fine;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use App\Services\Api\Borrowing\FineCalculator;
use App\Services\Api\Pickup\PickupBroadcastService;
use App\Services\Api\Qr\QrPayloadService;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Support\StaffAccessService;
use App\Services\AppSettingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StaffQrScanService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly QrPayloadService $qrPayloadService,
        private readonly BorrowingReservationExpiryService $reservationExpiryService,
        private readonly StaffBorrowingService $staffBorrowingService,
        private readonly AppSettingService $appSettingService,
        private readonly ActivityLogService $activityLogService,
        private readonly PickupBroadcastService $pickupBroadcastService,
        private readonly FineCalculator $fineCalculator
    ) {
    }

    public function scanPickup(User $staff, string $rawQr): array
    {
        return DB::transaction(function () use ($staff, $rawQr) {
            $staffBranchId = $this->staffAccessService->branchId($staff);
            [$payload, $qr] = $this->resolveQr($rawQr, QrCodeType::BORROWING_PICKUP);

            $borrowing = $this->lockBorrowing((string) $payload['borrowing_id']);
            $borrowingBranchId = $this->resolveBorrowingBranchId($borrowing);
            $this->staffAccessService->assertSameBranch($staff, $borrowingBranchId);

            if ((string) ($payload['branch_id'] ?? '') !== (string) $staffBranchId) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'QR tidak sesuai dengan cabang staff.', 'qr_scan');

                throw ValidationException::withMessages([
                    'qr' => ['QR tidak sesuai dengan cabang staff.'],
                ]);
            }

            if ($borrowing->status !== Borrowing::AWAITING_PICKUP) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Transaksi ini tidak sedang menunggu pengambilan buku.', 'qr_scan');

                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini tidak sedang menunggu pengambilan buku.'],
                ]);
            }

            if ($borrowing->reservation_expires_at && $borrowing->reservation_expires_at->isPast()) {
                $this->reservationExpiryService->expireOne($borrowing->id);

                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Reservasi peminjaman sudah kedaluwarsa.', 'qr_scan');

                throw ValidationException::withMessages([
                    'borrowing' => ['Reservasi peminjaman sudah kedaluwarsa. Stock telah dilepas kembali.'],
                ]);
            }

            $items = BorrowingItem::query()
                ->with(['book', 'bookItem'])
                ->where('borrowing_id', $borrowing->id)
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Tidak ada item peminjaman pada transaksi ini.'],
                ]);
            }

            $bookItemIds = $items->pluck('book_item_id')->filter()->unique()->values();

            $bookItems = BookItem::query()
                ->whereIn('id', $bookItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $bookItem = $bookItems->get($item->book_item_id);

                if (! $bookItem || $bookItem->status !== BookItem::RESERVED) {
                    $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, "Buku '{$item->book?->title}' tidak dalam status reserved.", 'qr_scan');

                    throw ValidationException::withMessages([
                        'book_item' => ["Buku '{$item->book?->title}' tidak dalam status reserved."],
                    ]);
                }

                $bookItem->forceFill([
                    'status' => BookItem::BORROWED,
                    'updated_by' => $staff->id,
                    'updated_at' => now(),
                ])->save();

                $item->forceFill([
                    'status' => BorrowingItem::ACTIVE,
                    'updated_at' => now(),
                ])->save();
            }

            $borrowing->forceFill([
                'status' => Borrowing::ACTIVE,
                'approved_by' => $staff->id,
                'approved_at' => now(),
                'pickup_method' => Borrowing::PICKUP_METHOD_QR_SCAN,
                'updated_by' => $staff->id,
                'updated_at' => now(),
            ])->save();

            $this->markQrUsed($qr, $staff);

            $this->activityLogService->logScan(
                branchId: (string) $staffBranchId,
                staffId: $staff->id,
                scanType: 'borrowing_pickup',
                referenceId: $borrowing->id
            );

            Log::info('[QR][SCAN] Pickup scan success', [
                'borrowing_id' => $borrowing->id,
                'qr_id' => $qr->id,
                'staff_id' => $staff->id,
            ]);

            $detail = $this->staffBorrowingService->findDetail($staff, $borrowing->id);

            $this->pickupBroadcastService->success($borrowing, Borrowing::PICKUP_METHOD_QR_SCAN);

            return [
                'borrowing' => $this->summary($detail),
                'payment_required' => false,
                'message' => 'Checkout/pengambilan buku berhasil diproses oleh staff.',
            ];
        }, 3);
    }

    /**
     * Staff bypass: confirm a pickup without scanning a QR. Runs every business
     * validation scanPickup() does (branch match, AWAITING_PICKUP status,
     * reservation not expired, book items RESERVED) except QR resolution.
     * Gated by config('lumina.staff_bypass.enabled') — disabled by default.
     */
    public function forcePickup(User $staff, string $borrowingId, ?string $notes = null): array
    {
        if (! config('lumina.staff_bypass.enabled')) {
            throw ValidationException::withMessages([
                'staff_bypass' => ['Staff bypass tidak diaktifkan pada konfigurasi server ini.'],
            ]);
        }

        return DB::transaction(function () use ($staff, $borrowingId, $notes) {
            $staffBranchId = $this->staffAccessService->branchId($staff);

            $borrowing = $this->lockBorrowing($borrowingId);
            $borrowingBranchId = $this->resolveBorrowingBranchId($borrowing);
            $this->staffAccessService->assertSameBranch($staff, $borrowingBranchId);

            if ($borrowing->status !== Borrowing::AWAITING_PICKUP) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Transaksi ini tidak sedang menunggu pengambilan buku.', 'staff_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini tidak sedang menunggu pengambilan buku.'],
                ]);
            }

            if ($borrowing->reservation_expires_at && $borrowing->reservation_expires_at->isPast()) {
                $this->reservationExpiryService->expireOne($borrowing->id);

                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Reservasi peminjaman sudah kedaluwarsa.', 'staff_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Reservasi peminjaman sudah kedaluwarsa. Stock telah dilepas kembali.'],
                ]);
            }

            $items = BorrowingItem::query()
                ->with(['book', 'bookItem'])
                ->where('borrowing_id', $borrowing->id)
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Tidak ada item peminjaman pada transaksi ini.'],
                ]);
            }

            $bookItemIds = $items->pluck('book_item_id')->filter()->unique()->values();

            $bookItems = BookItem::query()
                ->whereIn('id', $bookItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $bookItem = $bookItems->get($item->book_item_id);

                if (! $bookItem || $bookItem->status !== BookItem::RESERVED) {
                    $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, "Buku '{$item->book?->title}' tidak dalam status reserved.", 'staff_bypass');

                    throw ValidationException::withMessages([
                        'book_item' => ["Buku '{$item->book?->title}' tidak dalam status reserved."],
                    ]);
                }

                $bookItem->forceFill([
                    'status' => BookItem::BORROWED,
                    'updated_by' => $staff->id,
                    'updated_at' => now(),
                ])->save();

                $item->forceFill([
                    'status' => BorrowingItem::ACTIVE,
                    'updated_at' => now(),
                ])->save();
            }

            $borrowing->forceFill([
                'status' => Borrowing::ACTIVE,
                'approved_by' => $staff->id,
                'approved_at' => now(),
                'pickup_method' => Borrowing::PICKUP_METHOD_STAFF_BYPASS,
                'notes' => $notes ?: $borrowing->notes,
                'updated_by' => $staff->id,
                'updated_at' => now(),
            ])->save();

            // Invalidate any still-active/refreshed pickup QR so it can never
            // be scanned after the bypass has already completed the pickup.
            $invalidatedQrIds = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', QrCodeType::BORROWING_PICKUP->value)
                ->whereIn('status', [BorrowingQrCode::ACTIVE, BorrowingQrCode::REFRESHED])
                ->lockForUpdate()
                ->pluck('id');

            if ($invalidatedQrIds->isNotEmpty()) {
                BorrowingQrCode::query()
                    ->whereIn('id', $invalidatedQrIds)
                    ->update([
                        'status' => BorrowingQrCode::USED,
                        'used_at' => now(),
                        'used_by' => $staff->id,
                        'updated_by' => $staff->id,
                        'updated_at' => now(),
                    ]);
            }

            $this->activityLogService->logScan(
                branchId: (string) $staffBranchId,
                staffId: $staff->id,
                scanType: 'staff_bypass_pickup',
                referenceId: $borrowing->id
            );

            Log::warning('[QR][BYPASS] Staff bypass pickup confirmed', [
                'borrowing_id' => $borrowing->id,
                'borrowing_code' => $borrowing->borrowing_code,
                'staff_id' => $staff->id,
                'branch_id' => $staffBranchId,
                'invalidated_qr_ids' => $invalidatedQrIds->all(),
            ]);

            $detail = $this->staffBorrowingService->findDetail($staff, $borrowing->id);

            $this->pickupBroadcastService->success($borrowing, Borrowing::PICKUP_METHOD_STAFF_BYPASS);

            return [
                'borrowing' => $this->summary($detail),
                'payment_required' => false,
                'message' => 'Pengambilan buku berhasil diproses oleh staff (bypass QR).',
            ];
        }, 3);
    }

    /**
     * Step 1 return flow: scan QR and preview borrowing detail.
     * This method DOES NOT return the book yet. Staff must confirm physical condition first.
     */
    public function previewReturn(User $staff, string $rawQr): array
    {
        return DB::transaction(function () use ($staff, $rawQr) {
            $staffBranchId = $this->staffAccessService->branchId($staff);
            [$payload, $qr] = $this->resolveQr($rawQr, QrCodeType::BORROWING_RETURN);

            $borrowing = $this->lockBorrowing((string) $payload['borrowing_id']);
            $borrowingBranchId = $this->resolveBorrowingBranchId($borrowing);
            $this->staffAccessService->assertSameBranch($staff, $borrowingBranchId);

            if ((string) ($payload['branch_id'] ?? '') !== (string) $staffBranchId) {
                throw ValidationException::withMessages([
                    'qr' => ['QR tidak sesuai dengan cabang staff.'],
                ]);
            }

            if ($borrowing->status === Borrowing::WAITING_PAYMENT) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Lunasi denda terlebih dahulu sebelum mengembalikan buku.'],
                ]);
            }

            if (! in_array($borrowing->status, [Borrowing::ACTIVE, Borrowing::READY_FOR_RETURN], true)) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini belum aktif atau sudah selesai.'],
                ]);
            }

            $activeItems = $this->activeBorrowingItems($borrowing->id);

            if ($activeItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Tidak ada item aktif untuk dikembalikan.'],
                ]);
            }

            $qr->forceFill([
                'status' => BorrowingQrCode::IN_REVIEW,
                'updated_by' => $staff->id,
                'updated_at' => now(),
            ])->save();

            $overdueDays = $this->fineCalculator->overdueDays($borrowing);
            $feeRules = $this->fineCalculator->feeRules();
            // Delegates to FineCalculator instead of re-deriving
            // overdueDays * fine_per_day * itemCount inline — see
            // MyBookService::attachComputedFields() for why that pattern
            // was a bug (divergent item-counting across call sites), not
            // just a naming issue.
            $totalFine = $this->fineCalculator->overdueFineAmount($borrowing);

            return [
                'return_review' => [
                    'qr_code' => $qr->qr_code,
                    'qr_status' => $qr->status,
                    'expires_at' => $qr->expires_at?->toISOString(),
                    'message' => 'QR valid. Staff dapat mengecek kondisi fisik buku sebelum konfirmasi pengembalian.',
                ],
                'borrowing' => [
                    'id' => $borrowing->id,
                    'code' => $borrowing->borrowing_code,
                    'status' => $overdueDays > 0 ? 'overdue' : Borrowing::ACTIVE,
                    'raw_status' => $borrowing->status,
                    'borrow_date' => $borrowing->borrow_date?->toDateString(),
                    'due_date' => $borrowing->due_date?->toDateString(),
                    'overdue_days' => $overdueDays,
                    'fine_per_day' => $feeRules['fine_per_day'],
                    'total_fine' => $totalFine,
                ],
                'member' => [
                    'id' => $borrowing->user?->id,
                    'fullname' => $borrowing->user?->fullname,
                    'email' => $borrowing->user?->email,
                    'phone' => $borrowing->user?->phone,
                    'member_code' => $borrowing->user?->member_code,
                ],
                'branch' => [
                    'id' => $borrowingBranchId,
                    'name' => $borrowing->branch?->name ?? $borrowing->libraryBranch?->name,
                    'code' => $borrowing->branch?->code ?? $borrowing->libraryBranch?->code,
                    'address' => $borrowing->branch?->address ?? $borrowing->libraryBranch?->address,
                ],
                'items' => $activeItems->map(fn (BorrowingItem $item) => $this->returnPreviewItem($item))->values(),
                'fee_rules' => $feeRules,
            ];
        }, 3);
    }

    /**
     * Backward-compatible direct return scan.
     * New mobile flow should use previewReturn() then confirmReturn().
     */
    public function scanReturn(User $staff, string $rawQr, array $payload = []): array
    {
        $preview = $this->previewReturn($staff, $rawQr);

        $items = collect($preview['items'] ?? [])
            ->map(fn (array $item) => [
                'borrowing_item_id' => $item['borrowing_item_id'],
                'book_item_id' => $item['book_item_id'],
                'return_condition' => $payload['return_condition'] ?? 'good',
                'notes' => $payload['notes'] ?? null,
            ])
            ->values()
            ->all();

        return $this->confirmReturn($staff, [
            'qr_code' => $preview['return_review']['qr_code'],
            'borrowing_id' => $preview['borrowing']['id'],
            'items' => $items,
            'staff_notes' => $payload['notes'] ?? null,
        ]);
    }

    /**
     * Step 2 return flow: staff confirms item conditions and backend finalizes return.
     */
    public function confirmReturn(User $staff, array $payload): array
    {
        return DB::transaction(function () use ($staff, $payload) {
            $qr = BorrowingQrCode::query()
                ->where('qr_code', (string) $payload['qr_code'])
                ->where('qr_type', QrCodeType::BORROWING_RETURN->value)
                ->whereIn('status', [BorrowingQrCode::IN_REVIEW, BorrowingQrCode::ACTIVE])
                ->lockForUpdate()
                ->first();

            if (! $qr) {
                throw ValidationException::withMessages([
                    'qr_code' => ['QR pengembalian tidak aktif atau tidak ditemukan.'],
                ]);
            }

            if ((string) $qr->borrowing_id !== (string) $payload['borrowing_id']) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['Data peminjaman tidak sesuai dengan QR.'],
                ]);
            }

            $borrowing = $this->lockBorrowing((string) $payload['borrowing_id']);
            $borrowingBranchId = $this->resolveBorrowingBranchId($borrowing);
            $this->staffAccessService->assertSameBranch($staff, $borrowingBranchId);

            if ($borrowing->status === Borrowing::WAITING_PAYMENT) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Lunasi denda terlebih dahulu sebelum mengembalikan buku.'],
                ]);
            }

            if (! in_array($borrowing->status, [Borrowing::ACTIVE, Borrowing::READY_FOR_RETURN], true)) {
                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini belum aktif atau sudah selesai.'],
                ]);
            }

            $submittedItems = collect($payload['items'] ?? [])->keyBy('borrowing_item_id');
            $items = BorrowingItem::query()
                ->with(['book', 'bookItem'])
                ->where('borrowing_id', $borrowing->id)
                ->whereIn('id', $submittedItems->keys()->all())
                ->where('status', BorrowingItem::ACTIVE)
                ->lockForUpdate()
                ->get();

            if ($items->count() !== $submittedItems->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Beberapa item pengembalian tidak valid atau sudah diproses.'],
                ]);
            }

            $bookItems = BookItem::query()
                ->whereIn('id', $items->pluck('book_item_id')->filter()->unique()->values())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $overdueDays = $this->fineCalculator->overdueDays($borrowing);
            $feeRules = $this->fineCalculator->feeRules();
            $fineRows = [];
            $returnedItems = [];
            $totalOverdueAmount = 0.0;
            $totalDamageAmount = 0.0;
            $totalLostAmount = 0.0;

            foreach ($items as $item) {
                $input = $submittedItems->get($item->id, []);
                $condition = $this->fineCalculator->normalizeReturnCondition((string) ($input['return_condition'] ?? 'good'));
                $notes = $input['notes'] ?? null;
                $bookItem = $bookItems->get($item->book_item_id);

                if (! $bookItem) {
                    throw ValidationException::withMessages([
                        'items' => ["Item fisik untuk buku '{$item->book?->title}' tidak ditemukan."],
                    ]);
                }

                $bookItem->forceFill([
                    'status' => $this->bookStatusAfterReturn($condition),
                    'updated_by' => $staff->id,
                    'updated_at' => now(),
                ])->save();

                $item->forceFill([
                    'status' => $condition === 'lost' ? BorrowingItem::LOST : BorrowingItem::RETURNED,
                    'return_date' => now()->toDateString(),
                    'return_condition' => $condition,
                    'notes' => $notes,
                    'updated_at' => now(),
                ])->save();

                $overdueAmount = $overdueDays * $feeRules['fine_per_day'];
                $damageAmount = $this->fineCalculator->damageFeeFor($condition, $feeRules);
                $lostAmount = $condition === 'lost' ? $feeRules['lost_fee'] : 0.0;
                $itemFineAmount = $overdueAmount + $damageAmount + $lostAmount;

                $totalOverdueAmount += $overdueAmount;
                $totalDamageAmount += $damageAmount;
                $totalLostAmount += $lostAmount;

                if ($itemFineAmount > 0) {
                    $fineType = match (true) {
                        $lostAmount > 0 => Fine::TYPE_LOST,
                        $damageAmount > 0 => Fine::TYPE_DAMAGE,
                        default => Fine::TYPE_OVERDUE,
                    };

                    $fineRows[] = Fine::query()->updateOrCreate(
                        ['borrowing_item_id' => $item->id],
                        [
                            'user_id' => $borrowing->user_id,
                            'branch_id' => $borrowingBranchId,
                            'fine_amount' => $itemFineAmount,
                            'overdue_days' => $overdueDays,
                            'status' => Fine::UNPAID,
                            'fine_type' => $fineType,
                            'notes' => $this->fineCalculator->fineNote($overdueDays, $overdueAmount, $damageAmount, $lostAmount, $condition),
                            'created_by' => $staff->id,
                            'updated_by' => $staff->id,
                        ]
                    );
                }

                $returnedItems[] = [
                    'borrowing_item_id' => $item->id,
                    'book_item_id' => $item->book_item_id,
                    'book_title' => $item->book?->title,
                    'return_condition' => $condition,
                    'status' => $item->status,
                    'overdue_amount' => $overdueAmount,
                    'damage_amount' => $damageAmount,
                    'lost_amount' => $lostAmount,
                    'fine_amount' => $itemFineAmount,
                ];
            }

            $totalFine = $totalOverdueAmount + $totalDamageAmount + $totalLostAmount;
            $paymentRequired = $totalFine > 0;

            $newBorrowingStatus = $paymentRequired
                ? Borrowing::WAITING_PAYMENT
                : Borrowing::RETURNED;

            $borrowing->forceFill([
                'status' => $newBorrowingStatus,
                'return_date' => now()->toDateString(),
                'notes' => $payload['staff_notes'] ?? $borrowing->notes,
                'updated_by' => $staff->id,
                'updated_at' => now(),
            ])->save();

            $this->markQrUsed($qr, $staff);

            $borrowingBranchIdForLog = $this->resolveBorrowingBranchId($borrowing);
            $this->activityLogService->logReturn(
                branchId: (string) $borrowingBranchIdForLog,
                userId: $staff->id,
                borrowingId: $borrowing->id,
                borrowingCode: $borrowing->borrowing_code,
                fineAmount: $totalFine
            );

            Log::info('[QR][SCAN] Return scan/confirm success', [
                'borrowing_id' => $borrowing->id,
                'qr_id' => $qr->id,
                'staff_id' => $staff->id,
                'total_fine' => $totalFine,
            ]);

            // Payment creation is no longer triggered here — the member
            // creates it themselves via POST /api/v1/payments/create when
            // they open the borrowing detail page and see WAITING_PAYMENT
            // (see App\Services\Api\Payment\BorrowingPaymentService and
            // docs/payment-flow.md). This keeps payment-method choice in
            // the member's hands, matching the Core API flow.

            $detail = $this->staffBorrowingService->findDetail($staff, $borrowing->id);

            if ($paymentRequired) {
                DB::afterCommit(function () use ($borrowing, $totalFine, $newBorrowingStatus) {
                    broadcast(new MyBookStatusUpdated(
                        userId: $borrowing->user_id,
                        payload: [
                            'event' => 'borrowing_waiting_payment',
                            'borrowing_id' => $borrowing->id,
                            'borrowing_code' => $borrowing->borrowing_code,
                            'status' => $newBorrowingStatus,
                            'payment_required' => true,
                            'fine_amount' => $totalFine,
                        ]
                    ));
                });
            } else {
                DB::afterCommit(function () use ($borrowing) {
                    \App\Events\Return\ReturnSuccess::dispatch($borrowing->user_id, $borrowing->id, $borrowing->borrowing_code);
                    Log::info('[WS] Broadcast sent: return.success', ['borrowing_id' => $borrowing->id, 'channel' => 'user.' . $borrowing->user_id]);
                });
            }

            return [
                'borrowing' => $this->summary($detail),
                'return_summary' => [
                    'is_overdue' => $overdueDays > 0,
                    'overdue_days' => $overdueDays,
                    'has_damage' => $totalDamageAmount > 0,
                    'has_lost' => $totalLostAmount > 0,
                    'payment_required' => $paymentRequired,
                ],
                'fine' => $paymentRequired ? [
                    'status' => Fine::UNPAID,
                    'overdue_amount' => $totalOverdueAmount,
                    'damage_amount' => $totalDamageAmount,
                    'lost_amount' => $totalLostAmount,
                    'total_amount' => $totalFine,
                    'items_count' => count($fineRows),
                ] : null,
                'items' => $returnedItems,
                'message' => $paymentRequired
                    ? 'Pengembalian berhasil diproses. Silakan selesaikan pembayaran denda melalui aplikasi.'
                    : 'Pengembalian berhasil diproses tanpa biaya.',
            ];
        }, 3);
    }

    public function scanBook(User $staff, array $payload): array
    {
        $branchId = $this->staffAccessService->branchId($staff);
        $bookItem = null;

        if (! empty($payload['qr_raw'])) {
            $decoded = json_decode((string) $payload['qr_raw'], true);

            if (is_array($decoded) && isset($decoded['type'])) {
                $verified = $this->qrPayloadService->verify($decoded, QrCodeType::BOOK_INTAKE);
                $this->staffAccessService->assertSameBranch($staff, (string) ($verified['branch_id'] ?? ''));

                $bookItem = $this->findBookItem(
                    branchId: $branchId,
                    bookItemId: $verified['book_item_id'] ?? null,
                    barcode: $verified['barcode'] ?? null,
                    itemCode: $verified['item_code'] ?? null
                );
            } else {
                $bookItem = $this->findBookItem($branchId, null, (string) $payload['qr_raw'], (string) $payload['qr_raw']);
            }
        }

        if (! $bookItem) {
            $bookItem = $this->findBookItem(
                branchId: $branchId,
                bookItemId: null,
                barcode: $payload['barcode'] ?? null,
                itemCode: $payload['item_code'] ?? null
            );
        }

        if (! $bookItem) {
            throw ValidationException::withMessages([
                'book' => ['Buku tidak ditemukan pada cabang staff.'],
            ]);
        }

        return [
            'scan_type' => 'book_item',
            'data' => (new StaffBookScanResource($bookItem))->resolve(request()),
        ];
    }

    private function resolveQr(string $rawQr, QrCodeType $type): array
    {
        try {
            $payload = $this->qrPayloadService->decode($rawQr);
            $this->qrPayloadService->verify($payload, $type);
        } catch (ValidationException $e) {
            Log::warning('[QR][SCAN] QR invalid (payload/signature)', [
                'qr_type' => $type->value,
                'errors' => $e->errors(),
            ]);
            throw $e;
        }

        $qr = BorrowingQrCode::query()
            ->where('qr_code', $payload['jti'] ?? null)
            ->where('qr_type', $type->value)
            ->lockForUpdate()
            ->first();

        if (! $qr) {
            Log::warning('[QR][SCAN] QR not found in database', [
                'qr_type' => $type->value,
                'jti' => $payload['jti'] ?? null,
            ]);

            throw ValidationException::withMessages([
                'qr' => ['QR tidak terdaftar pada sistem Lumina Library.'],
            ]);
        }

        if ($qr->status !== BorrowingQrCode::ACTIVE) {
            Log::warning('[QR][SCAN] QR scan failed — not active', [
                'qr_id' => $qr->id,
                'status' => $qr->status,
                'borrowing_id' => $qr->borrowing_id,
            ]);

            if ($type === QrCodeType::BORROWING_PICKUP) {
                $this->pickupBroadcastService->failed((string) $qr->borrowing_id, null, 'QR sudah tidak aktif.', 'qr_scan');
            }

            throw ValidationException::withMessages([
                'qr' => ['QR sudah tidak aktif.'],
            ]);
        }

        if ($qr->expires_at && $qr->expires_at->isPast()) {
            Log::warning('[QR][SCAN] QR scan failed — expired', [
                'qr_id' => $qr->id,
                'expires_at' => $qr->expires_at?->toISOString(),
                'borrowing_id' => $qr->borrowing_id,
            ]);

            if ($type === QrCodeType::BORROWING_PICKUP) {
                $this->pickupBroadcastService->failed((string) $qr->borrowing_id, null, 'QR sudah kedaluwarsa.', 'qr_scan');
            }

            throw ValidationException::withMessages([
                'qr' => ['QR sudah kedaluwarsa. Silakan refresh QR.'],
            ]);
        }

        if ((string) $qr->borrowing_id !== (string) ($payload['borrowing_id'] ?? '')) {
            Log::warning('[QR][SCAN] QR scan failed — borrowing mismatch', [
                'qr_id' => $qr->id,
                'qr_borrowing_id' => $qr->borrowing_id,
                'payload_borrowing_id' => $payload['borrowing_id'] ?? null,
            ]);

            throw ValidationException::withMessages([
                'qr' => ['Data QR tidak sesuai dengan transaksi.'],
            ]);
        }

        return [$payload, $qr];
    }

    private function lockBorrowing(string $borrowingId): Borrowing
    {
        $borrowing = Borrowing::query()
            ->with(['user', 'branch', 'libraryBranch'])
            ->where('id', $borrowingId)
            ->lockForUpdate()
            ->first();

        if (! $borrowing) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
            ]);
        }

        return $borrowing;
    }

    private function activeBorrowingItems(string $borrowingId): Collection
    {
        return BorrowingItem::query()
            ->with(['book.category', 'bookItem.rack'])
            ->where('borrowing_id', $borrowingId)
            ->where('status', BorrowingItem::ACTIVE)
            ->lockForUpdate()
            ->get();
    }

    private function returnPreviewItem(BorrowingItem $item): array
    {
        $book = $item->book;
        $bookItem = $item->bookItem;
        $rack = $bookItem?->rack;

        return [
            'borrowing_item_id' => $item->id,
            'book_id' => $item->book_id,
            'book_item_id' => $item->book_item_id,
            'current_status' => $item->status,
            'book' => [
                'id' => $book?->id,
                'title' => $book?->title,
                'author' => $book?->author,
                'isbn' => $book?->isbn,
            ],
            'physical_item' => [
                'id' => $bookItem?->id,
                'item_code' => $bookItem?->item_code,
                'barcode' => $bookItem?->barcode,
                'status' => $bookItem?->status,
                'rack' => $rack ? [
                    'id' => $rack->id,
                    'code' => $rack->rack_code,
                    'name' => $rack->name,
                    'floor_label' => $rack->floor_label,
                    'area_label' => $rack->area_label,
                ] : null,
            ],
            'condition_options' => [
                ['value' => 'good', 'label' => 'Baik', 'fee' => 0],
                ['value' => 'minor_damage', 'label' => 'Rusak Ringan', 'fee' => $this->fineCalculator->feeRules()['minor_damage_fee']],
                ['value' => 'major_damage', 'label' => 'Rusak Berat', 'fee' => $this->fineCalculator->feeRules()['major_damage_fee']],
                ['value' => 'lost', 'label' => 'Hilang', 'fee' => $this->fineCalculator->feeRules()['lost_fee']],
            ],
        ];
    }

    private function markQrUsed(BorrowingQrCode $qr, User $staff): void
    {
        $qr->forceFill([
            'status' => BorrowingQrCode::USED,
            'used_at' => now(),
            'used_by' => $staff->id,
            'updated_by' => $staff->id,
            'updated_at' => now(),
        ])->save();
    }

    private function resolveBorrowingBranchId(Borrowing $borrowing): ?string
    {
        return $borrowing->branch_id ?: $borrowing->library_branch_id;
    }

    private function bookStatusAfterReturn(string $condition): string
    {
        return match ($condition) {
            'lost' => BookItem::LOST,
            'major_damage' => BookItem::DAMAGED,
            default => BookItem::AVAILABLE,
        };
    }

    private function findBookItem(string $branchId, ?string $bookItemId = null, ?string $barcode = null, ?string $itemCode = null): ?BookItem
    {
        return BookItem::query()
            ->with(['book.category', 'branch', 'rack'])
            ->where('branch_id', $branchId)
            ->when($bookItemId, fn ($query) => $query->where('id', $bookItemId))
            ->when(! $bookItemId && ($barcode || $itemCode), function ($query) use ($barcode, $itemCode) {
                $query->where(function ($query) use ($barcode, $itemCode) {
                    if ($barcode) {
                        $query->orWhere('barcode', $barcode);
                    }

                    if ($itemCode) {
                        $query->orWhere('item_code', $itemCode);
                    }
                });
            })
            ->first();
    }

    private function summary(Borrowing $borrowing): array
    {
        return [
            'id' => $borrowing->id,
            'code' => $borrowing->borrowing_code,
            'raw_status' => $borrowing->status,
            'computed_status' => $borrowing->computed_status ?? $borrowing->status,
            'borrow_date' => $borrowing->borrow_date?->toDateString(),
            'due_date' => $borrowing->due_date?->toDateString(),
            'return_date' => $borrowing->return_date?->toDateString(),
            'days_remaining' => (int) ($borrowing->days_remaining ?? 0),
            'overdue_days' => (int) ($borrowing->overdue_days ?? 0),
            'fine_per_day' => (float) ($borrowing->fine_per_day ?? 0),
            'total_fine' => (float) ($borrowing->total_fine ?? 0),
        ];
    }
}
