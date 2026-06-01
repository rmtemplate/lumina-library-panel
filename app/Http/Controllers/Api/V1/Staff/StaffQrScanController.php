<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffBookScanRequest;
use App\Http\Requests\Api\Staff\StaffQrScanRequest;
use App\Http\Requests\Api\Staff\StaffReturnConfirmRequest;
use App\Services\Api\Staff\Qr\StaffQrScanService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaffQrScanController extends ApiController
{
    public function __construct(
        private readonly StaffQrScanService $staffQrScanService
    ) {
    }

    public function scanPickup(StaffQrScanRequest $request): JsonResponse
    {
        $result = $this->staffQrScanService->scanPickup(
            staff: $request->user(),
            rawQr: $request->validated('qr_raw')
        );

        return ApiResponse::success(
            data: $result,
            message: 'Checkout/pengambilan buku berhasil diproses oleh staff.',
            code: 'STAFF_QR_PICKUP_SUCCESS'
        );
    }

    public function previewReturn(StaffQrScanRequest $request): JsonResponse
    {
        $result = $this->staffQrScanService->previewReturn(
            staff: $request->user(),
            rawQr: $request->validated('qr_raw')
        );

        return ApiResponse::success(
            data: $result,
            message: 'QR valid. Silakan cek kondisi buku sebelum konfirmasi pengembalian.',
            code: 'RETURN_SCAN_PREVIEW_SUCCESS'
        );
    }

    /**
     * Backward-compatible direct return scan.
     * Prefer using previewReturn() + confirmReturn() for mobile staff flow.
     */
    public function scanReturn(StaffQrScanRequest $request): JsonResponse
    {
        $result = $this->staffQrScanService->scanReturn(
            staff: $request->user(),
            rawQr: $request->validated('qr_raw'),
            payload: $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: 'Pengembalian buku berhasil diproses oleh staff.',
            code: 'STAFF_QR_RETURN_SUCCESS'
        );
    }

    public function confirmReturn(StaffReturnConfirmRequest $request): JsonResponse
    {
        $result = $this->staffQrScanService->confirmReturn(
            staff: $request->user(),
            payload: $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: $result['return_summary']['payment_required']
                ? 'Pengembalian berhasil diproses. Terdapat biaya yang perlu dibayar.'
                : 'Pengembalian berhasil diproses.',
            code: 'RETURN_CONFIRM_SUCCESS'
        );
    }

    public function scanBook(StaffBookScanRequest $request): JsonResponse
    {
        $result = $this->staffQrScanService->scanBook(
            staff: $request->user(),
            payload: $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: 'Data buku hasil scan berhasil diambil.',
            code: 'STAFF_BOOK_SCAN_SUCCESS'
        );
    }
}
