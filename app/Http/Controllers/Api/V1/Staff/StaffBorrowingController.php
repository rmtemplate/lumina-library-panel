<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffBorrowingIndexRequest;
use App\Http\Resources\Api\Staff\StaffBorrowingDetailResource;
use App\Http\Resources\Api\Staff\StaffBorrowingResource;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Qr\StaffQrScanService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffBorrowingController extends ApiController
{
    public function __construct(
        private readonly StaffBorrowingService $staffBorrowingService,
        private readonly StaffQrScanService $staffQrScanService
    ) {
    }

    public function index(StaffBorrowingIndexRequest $request): JsonResponse
    {
        $borrowings = $this->staffBorrowingService->paginate(
            staff: $request->user(),
            filters: $request->validated()
        );

        return ApiResponse::paginated(
            paginator: $borrowings,
            data: StaffBorrowingResource::collection($borrowings),
            message: 'Daftar peminjaman staff berhasil diambil.',
            code: 'STAFF_BORROWING_LIST_SUCCESS',
            meta: [
                'filters' => [
                    'status' => $request->validated('status'),
                    'search' => $request->validated('search'),
                ],
            ]
        );
    }

    public function show(Request $request, string $borrowing): JsonResponse
    {
        $detail = $this->staffBorrowingService->findDetail(
            staff: $request->user(),
            borrowingId: $borrowing
        );

        return ApiResponse::success(
            data: new StaffBorrowingDetailResource($detail),
            message: 'Detail peminjaman staff berhasil diambil.',
            code: 'STAFF_BORROWING_DETAIL_SUCCESS'
        );
    }

    /**
     * Dev/testing/emergency override — confirm pickup without scanning a QR.
     * Disabled unless config('lumina.staff_bypass.enabled') is true.
     */
    public function forcePickup(Request $request, string $borrowing): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->staffQrScanService->forcePickup(
            staff: $request->user(),
            borrowingId: $borrowing,
            notes: $validated['notes'] ?? null
        );

        return ApiResponse::success(
            data: $result,
            message: $result['message'],
            code: 'STAFF_BYPASS_PICKUP_SUCCESS'
        );
    }
}
