<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\UniversalScanRequest;
use App\Services\Api\Staff\Scan\StaffUniversalScanService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class StaffUniversalScanController extends ApiController
{
    public function __construct(
        private readonly StaffUniversalScanService $service
    ) {
    }

    /**
     * POST /api/v1/staff/qr/scan
     *
     * The ONE scan endpoint the Universal QR Scanner calls — staff never
     * picks a mode. Response shape: { type, data }, where `type` is one of
     * borrowing_pickup | borrowing_return | book_intake | book_not_found.
     */
    public function scan(UniversalScanRequest $request): JsonResponse
    {
        $result = $this->service->identify($request->user(), (string) $request->validated('raw'));

        return ApiResponse::success(
            data: $result,
            message: 'Scan berhasil diproses.',
            code: 'STAFF_SCAN_SUCCESS'
        );
    }
}
