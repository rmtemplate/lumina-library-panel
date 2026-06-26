<?php

namespace App\Services\Api\Dev;

use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\ActivityLog;
use App\Models\BorrowingQrCode;
use App\Services\ActivityLogService;
use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use App\Services\Api\MyBook\MyBookService;
use App\Services\Api\Pickup\PickupBroadcastService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * LOCAL DEVELOPMENT ONLY. Lets a single-device tester complete a pickup
 * without logging in as staff and without scanning a QR.
 *
 * Deliberately kept as its own service (not a method on StaffQrScanService)
 * so the "no auth, no QR" code path is easy to audit, easy to delete
 * wholesale, and impossible to reach by accident from any authenticated
 * flow. Access to the controller that calls this is gated entirely by
 * App\Http\Middleware\EnsureDevBypassEnabled — this service assumes the
 * caller has already verified it's safe to run.
 *
 * Business validation mirrors StaffQrScanService::scanPickup() exactly
 * (minus the staff/branch/QR checks, which don't apply here): borrowing
 * exists, not cancelled, not already picked up, not expired, deadline still
 * valid, and every book item is still RESERVED.
 */
class DevBypassService
{
    public function __construct(
        private readonly BorrowingReservationExpiryService $reservationExpiryService,
        private readonly MyBookService $myBookService,
        private readonly ActivityLogService $activityLogService,
        private readonly PickupBroadcastService $pickupBroadcastService
    ) {
    }

    public function forcePickup(string $borrowingId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($borrowingId, $notes) {
            $borrowing = Borrowing::query()
                ->where('id', $borrowingId)
                ->lockForUpdate()
                ->first();

            if (! $borrowing) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['Data peminjaman tidak ditemukan.'],
                ]);
            }

            if ($borrowing->status === Borrowing::CANCELLED) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Transaksi ini sudah dibatalkan.', 'dev_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini sudah dibatalkan.'],
                ]);
            }

            if (in_array($borrowing->status, [
                Borrowing::ACTIVE,
                Borrowing::RETURNED,
                Borrowing::RETURNED_WITH_FINE,
                Borrowing::WAITING_PAYMENT,
            ], true)) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Buku pada transaksi ini sudah diambil.', 'dev_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Buku pada transaksi ini sudah diambil.'],
                ]);
            }

            if ($borrowing->status === Borrowing::EXPIRED) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Reservasi peminjaman sudah kedaluwarsa.', 'dev_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Reservasi peminjaman sudah kedaluwarsa. Stock telah dilepas kembali.'],
                ]);
            }

            if ($borrowing->status !== Borrowing::AWAITING_PICKUP) {
                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Transaksi ini tidak sedang menunggu pengambilan buku.', 'dev_bypass');

                throw ValidationException::withMessages([
                    'borrowing' => ['Transaksi ini tidak sedang menunggu pengambilan buku.'],
                ]);
            }

            if ($borrowing->reservation_expires_at && $borrowing->reservation_expires_at->isPast()) {
                $this->reservationExpiryService->expireOne($borrowing->id);

                Log::warning('[QR][DEV_BYPASS] Pickup deadline already passed', [
                    'borrowing_id' => $borrowing->id,
                ]);

                $this->pickupBroadcastService->failed($borrowing->id, $borrowing->borrowing_code, 'Reservasi peminjaman sudah kedaluwarsa.', 'dev_bypass');

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
                    throw ValidationException::withMessages([
                        'book_item' => ["Buku '{$item->book?->title}' tidak dalam status reserved."],
                    ]);
                }

                $bookItem->forceFill([
                    'status' => BookItem::BORROWED,
                    'updated_at' => now(),
                ])->save();

                $item->forceFill([
                    'status' => BorrowingItem::ACTIVE,
                    'updated_at' => now(),
                ])->save();
            }

            // No real staff actor exists for this flow — approved_by stays
            // null (it's a nullable FK to users) and the simulated actor is
            // recorded only in pickup_method + the activity log metadata.
            $borrowing->forceFill([
                'status' => Borrowing::ACTIVE,
                'approved_at' => now(),
                'pickup_method' => Borrowing::PICKUP_METHOD_DEV_BYPASS,
                'notes' => $notes ?: $borrowing->notes,
                'updated_at' => now(),
            ])->save();

            // Invalidate any still-active/refreshed pickup QR so a stale code
            // can never be scanned after the bypass already completed pickup.
            BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', 'borrowing_pickup')
                ->whereIn('status', [BorrowingQrCode::ACTIVE, BorrowingQrCode::REFRESHED])
                ->update([
                    'status' => BorrowingQrCode::USED,
                    'used_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->activityLogService->log(
                activityType: ActivityLog::SCAN,
                title: 'Pengambilan buku (LOCAL DEV BYPASS — tanpa QR, tanpa login staff)',
                branchId: $borrowing->branch_id ?: $borrowing->library_branch_id,
                userId: null,
                subtitle: $borrowing->borrowing_code,
                referenceType: 'borrowing',
                referenceId: $borrowing->id,
                metadata: ['picked_by' => 'LOCAL_DEV', 'pickup_method' => Borrowing::PICKUP_METHOD_DEV_BYPASS]
            );

            Log::warning('[QR][DEV_BYPASS] Pickup force-completed without auth or QR', [
                'borrowing_id' => $borrowing->id,
                'borrowing_code' => $borrowing->borrowing_code,
                'picked_by' => 'LOCAL_DEV',
                'env' => app()->environment(),
            ]);

            // Same pickup.success event, same payload shape as a real QR scan
            // (StaffQrScanService::scanPickup) — the Flutter app cannot tell
            // the difference between the two pickup paths. The dev bypass
            // ALSO additionally fires staff.force-pickup (actor "LOCAL_DEV")
            // since this path skipped both QR verification and staff auth.
            $this->pickupBroadcastService->success($borrowing, Borrowing::PICKUP_METHOD_DEV_BYPASS);

            $detail = $this->myBookService->findDetail($borrowing->user, $borrowing->id);

            return [
                'borrowing' => $this->summary($detail),
                'payment_required' => false,
                'message' => 'Checkout/pengambilan buku berhasil diproses (LOCAL DEV BYPASS).',
            ];
        }, 3);
    }

    private function summary(Borrowing $borrowing): array
    {
        return [
            'id' => $borrowing->id,
            'code' => $borrowing->borrowing_code,
            'raw_status' => $borrowing->status,
            'computed_status' => $borrowing->getAttribute('computed_status') ?? $borrowing->status,
            'borrow_date' => $borrowing->borrow_date?->toDateString(),
            'due_date' => $borrowing->due_date?->toDateString(),
            'return_date' => $borrowing->return_date?->toDateString(),
            'days_remaining' => (int) ($borrowing->getAttribute('days_remaining') ?? 0),
            'overdue_days' => (int) ($borrowing->getAttribute('overdue_days') ?? 0),
            'estimated_fine' => (float) ($borrowing->getAttribute('estimated_fine') ?? 0),
            'pickup_method' => $borrowing->pickup_method,
        ];
    }
}
