<?php

namespace App\Services\Api\Qr;

use App\Enums\QrCodeType;
use App\Models\BorrowingQrCode;
use Illuminate\Validation\ValidationException;

class QrScanValidationService
{
    public function __construct(
        private readonly QrPayloadService $qrPayloadService
    ) {
    }

    public function validateBorrowingPickupQr(string $rawQr): array
    {
        $payload = $this->qrPayloadService->decode($rawQr);

        $this->qrPayloadService->verify(
            payload: $payload,
            expectedType: QrCodeType::BORROWING_PICKUP
        );

        $qr = BorrowingQrCode::query()
            ->where('qr_code', $payload['jti'])
            ->where('qr_type', QrCodeType::BORROWING_PICKUP->value)
            ->first();

        if (! $qr) {
            throw ValidationException::withMessages([
                'qr' => ['QR tidak terdaftar pada sistem Lumina Library.'],
            ]);
        }

        if ($qr->status !== 'active') {
            throw ValidationException::withMessages([
                'qr' => ['QR sudah tidak aktif.'],
            ]);
        }

        if ($qr->expires_at && $qr->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'qr' => ['QR sudah kedaluwarsa.'],
            ]);
        }

        if ((string) $qr->borrowing_id !== (string) ($payload['borrowing_id'] ?? '')) {
            throw ValidationException::withMessages([
                'qr' => ['Data QR tidak sesuai dengan transaksi.'],
            ]);
        }

        return [
            'payload' => $payload,
            'qr' => $qr,
        ];
    }
}