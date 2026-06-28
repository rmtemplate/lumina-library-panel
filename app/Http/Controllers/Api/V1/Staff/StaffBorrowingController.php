<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffBorrowingIndexRequest;
use App\Http\Requests\Api\Staff\StaffCreateBorrowingRequest;
use App\Http\Resources\Api\Staff\StaffBorrowingDetailResource;
use App\Http\Resources\Api\Staff\StaffBorrowingResource;
use App\Services\Api\Staff\Borrowing\StaffBorrowCartService;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Qr\StaffQrScanService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffBorrowingController extends ApiController
{
    public function __construct(
        private readonly StaffBorrowingService $staffBorrowingService,
        private readonly StaffQrScanService $staffQrScanService,
        private readonly StaffBorrowCartService $staffBorrowCartService
    ) {
    }

    /**
     * POST /api/v1/staff/borrowings
     *
     * Borrow Cart confirm — staff scanned/searched books into a cart and
     * identified the member; this creates the Borrowing as ACTIVE
     * immediately (books are handed over now, no pickup QR step).
     */
    public function create(StaffCreateBorrowingRequest $request): JsonResponse
    {
        $result = $this->staffBorrowCartService->create(
            staff: $request->user(),
            memberId: (string) $request->validated('member_id'),
            bookItemIds: (array) $request->validated('book_item_ids'),
            notes: $request->validated('notes')
        );

        return ApiResponse::success(
            data: ['borrowing' => new StaffBorrowingDetailResource($result['borrowing'])],
            message: $result['message'],
            code: 'STAFF_BORROWING_CREATED'
        );
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
