<?php

namespace App\Services\Api\Staff\Scan;

use App\Enums\QrCodeType;
use App\Models\User;
use App\Services\Api\Staff\Qr\StaffQrScanService;
use Illuminate\Validation\ValidationException;

/**
 * Single entry point for the Universal QR Scanner — staff never chooses a
 * scan "mode"; this service peeks at the decoded payload's `type` field
 * (without trusting it yet — StaffQrScanService::scanPickup()/previewReturn()
 * still run full signature verification before doing anything) and routes
 * to the right existing handler. No business logic is duplicated here —
 * this is pure orchestration on top of StaffQrScanService, which already
 * does everything pickup/return/book scanning needs.
 */
class StaffUniversalScanService
{
    public function __construct(
        private readonly StaffQrScanService $staffQrScanService
    ) {
    }

    /**
     * @return array{type: string, data: array}
     */
    public function identify(User $staff, string $rawQr): array
    {
        $type = $this->peekType($rawQr);

        return match ($type) {
            QrCodeType::BORROWING_PICKUP => [
                'type' => QrCodeType::BORROWING_PICKUP->value,
                'data' => $this->staffQrScanService->scanPickup($staff, $rawQr),
            ],
            QrCodeType::BORROWING_RETURN => [
                'type' => QrCodeType::BORROWING_RETURN->value,
                'data' => $this->staffQrScanService->previewReturn($staff, $rawQr),
            ],
            // book_intake, or anything unrecognized (plain barcode/item_code,
            // not even JSON) — scanBook() already has its own internal
            // fallback chain (signed payload -> raw barcode/item_code), so we
            // just hand the raw value through.
            default => $this->resolveBook($staff, $rawQr),
        };
    }

    /**
     * Peeks at the `type` field without verifying signature — verification
     * happens for real inside scanPickup()/previewReturn() themselves. This
     * is only used to decide WHICH of those to call; a forged/garbled type
     * here just routes to the wrong handler, which then rejects it via its
     * own signature check, so peeking unverified is safe.
     */
    private function peekType(string $rawQr): ?QrCodeType
    {
        $decoded = json_decode($rawQr, true);

        if (! is_array($decoded) || empty($decoded['type'])) {
            return null;
        }

        return QrCodeType::tryFrom((string) $decoded['type']);
    }

    private function resolveBook(User $staff, string $rawQr): array
    {
        try {
            $result = $this->staffQrScanService->scanBook($staff, ['qr_raw' => $rawQr]);

            return [
                'type' => QrCodeType::BOOK_INTAKE->value,
                'data' => $result,
            ];
        } catch (ValidationException) {
            // Not an error from the staff's point of view — this is the
            // explicit "Book not registered" path the UI offers Register
            // Book / Cancel for, not a failure to surface as a generic toast.
            return [
                'type' => 'book_not_found',
                'data' => ['raw' => $rawQr],
            ];
        }
    }
}
