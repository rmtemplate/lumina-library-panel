<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\Api\Qr\QrRefreshService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QrController extends ApiController
{
    public function __construct(
        private readonly QrRefreshService $qrRefreshService
    ) {
    }

    /**
     * Refresh QR peminjaman/pickup.
     *
     * Flow:
     * - User sudah checkout.
     * - Borrowing status masih awaiting_pickup.
     * - Reservation belum expired.
     * - QR lama akan diubah menjadi refreshed.
     * - QR baru dibuat dengan TTL pendek, misalnya 60 detik.
     */
    public function refreshBorrowingPickup(Request $request, string $borrowing): JsonResponse
    {
        if (! $request->user()) {
            throw ValidationException::withMessages([
                'auth' => ['Token tidak valid atau belum login.'],
            ]);
        }

        $result = $this->qrRefreshService->refreshBorrowingPickupQr(
            user: $request->user(),
            borrowingId: $borrowing
        );

        return ApiResponse::success(
            data: $result,
            message: 'QR berhasil diperbarui.',
            code: 'QR_REFRESH_SUCCESS'
        );
    }
}