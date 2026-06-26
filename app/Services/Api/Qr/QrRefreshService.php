<?php

namespace App\Services\Api\Qr;

use App\Enums\QrCodeType;
use App\Events\Qr\QrGenerated;
use App\Events\Qr\QrRefreshed;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingQrCode;
use App\Models\User;
use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QrRefreshService
{
    public function __construct(
        private readonly QrPayloadService $qrPayloadService,
        private readonly QrImageService $qrImageService,
        private readonly BorrowingReservationExpiryService $reservationExpiryService
    ) {
    }

    public function refreshBorrowingPickupQr(User $user, string $borrowingId): array
    {
        return DB::transaction(function () use ($user, $borrowingId) {
            $borrowing = Borrowing::query()
                ->where('id', $borrowingId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $borrowing) {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['Transaksi peminjaman tidak ditemukan.'],
                ]);
            }

            if ($borrowing->status !== 'awaiting_pickup') {
                throw ValidationException::withMessages([
                    'borrowing_id' => ['QR hanya dapat direfresh untuk transaksi yang menunggu pengambilan.'],
                ]);
            }

            if ($borrowing->reservation_expires_at && $borrowing->reservation_expires_at->isPast()) {
                $this->reservationExpiryService->expireOne($borrowing->id);

                throw ValidationException::withMessages([
                    'borrowing_id' => ['Reservasi peminjaman sudah kedaluwarsa. Silakan checkout ulang.'],
                ]);
            }

            $hadAnyPreviousQr = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', QrCodeType::BORROWING_PICKUP->value)
                ->exists();

            $previousQr = BorrowingQrCode::query()
                ->where('borrowing_id', $borrowing->id)
                ->where('qr_type', QrCodeType::BORROWING_PICKUP->value)
                ->where('status', 'active')
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($previousQr) {
                $previousQr->forceFill([
                    'status' => 'refreshed',
                    'updated_at' => now(),
                    'updated_by' => $user->id,
                ])->save();
            }

            $ttlSeconds = (int) config('lumina.qr.pickup_ttl_seconds', 60);
            $expiresAt = now()->addSeconds($ttlSeconds);
            $qrCode = $this->generateQrCodeToken();

            $branchId = $borrowing->branch_id ?? $borrowing->library_branch_id;

            $payload = $this->qrPayloadService->makePayload(
                QrCodeType::BORROWING_PICKUP,
                [
                    'qr_code' => $qrCode,
                    'borrowing_id' => $borrowing->id,
                    'borrowing_code' => $borrowing->borrowing_code,
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                    'expires_at' => $expiresAt,
                ]
            );

            $newQr = BorrowingQrCode::query()->create([
                'id' => (string) Str::uuid(),
                'refreshed_from_id' => $previousQr?->id,
                'borrowing_id' => $borrowing->id,
                'qr_code' => $qrCode,
                'qr_type' => QrCodeType::BORROWING_PICKUP->value,
                'status' => 'active',
                'refresh_count' => ((int) ($previousQr?->refresh_count ?? 0)) + 1,
                'payload' => $payload,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'used_by' => null,
                'last_refreshed_at' => now(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('[QR][REFRESH] Pickup QR refreshed', [
                'borrowing_id' => $borrowing->id,
                'previous_qr_id' => $previousQr?->id,
                'new_qr_id' => $newQr->id,
                'refresh_count' => $newQr->refresh_count,
                'expires_at' => $newQr->expires_at?->toISOString(),
                'user_id' => $user->id,
            ]);

            DB::afterCommit(function () use ($user, $newQr, $previousQr, $hadAnyPreviousQr) {
                if ($hadAnyPreviousQr) {
                    QrRefreshed::dispatch($user->id, $newQr, $previousQr?->id);
                    Log::info('[WS] Broadcast sent: qr.refreshed', ['qr_id' => $newQr->id, 'channel' => 'user.' . $user->id]);
                } else {
                    QrGenerated::dispatch($user->id, $newQr);
                    Log::info('[WS] Broadcast sent: qr.generated', ['qr_id' => $newQr->id, 'channel' => 'user.' . $user->id]);
                }
            });

            return [
                'borrowing' => [
                    'id' => $borrowing->id,
                    'code' => $borrowing->borrowing_code,
                    'status' => $borrowing->status,
                    'reservation_expires_at' => $borrowing->reservation_expires_at?->toISOString(),
                ],
                'qr' => [
                    'id' => $newQr->id,
                    'code' => $newQr->qr_code,
                    'type' => $newQr->qr_type,
                    'type_label' => QrCodeType::BORROWING_PICKUP->label(),
                    'status' => $newQr->status,
                    'expires_at' => $newQr->expires_at?->toISOString(),
                    'refresh_count' => (int) $newQr->refresh_count,
                    'ttl_seconds' => $ttlSeconds,
                    'image' => $this->qrImageService->makeDataUri($payload),
                ],
            ];
        }, 3);
    }

    private function expireBorrowingAndReleaseStock(Borrowing $borrowing): void
    {
        $borrowing->loadMissing(['items.bookItem']);

        foreach ($borrowing->items as $item) {
            if ($item->bookItem && $item->bookItem->status === BookItem::RESERVED) {
                $item->bookItem->forceFill([
                    'status' => BookItem::AVAILABLE,
                    'updated_at' => now(),
                ])->save();
            }

            $item->forceFill([
                'status' => 'expired',
                'updated_at' => now(),
            ])->save();
        }

        BorrowingQrCode::query()
            ->where('borrowing_id', $borrowing->id)
            ->where('status', 'active')
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);

        $borrowing->forceFill([
            'status' => 'expired',
            'updated_at' => now(),
        ])->save();
    }

    private function generateQrCodeToken(): string
    {
        // UUIDv4 entropy makes collisions negligible, so we generate once
        // instead of an exists()-check-then-create loop (which is a TOCTOU
        // race under concurrent refreshes). The `qr_code` UNIQUE constraint
        // remains as a DB-level safety net.
        return 'QR-' . strtoupper(str_replace('-', '', (string) Str::uuid()));
    }
}