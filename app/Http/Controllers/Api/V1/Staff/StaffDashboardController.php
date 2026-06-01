<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Api\Staff\StaffDashboardResource;
use App\Services\Api\Staff\Dashboard\StaffDashboardService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffDashboardController extends ApiController
{
    public function __construct(
        private readonly StaffDashboardService $staffDashboardService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->staffDashboardService->getDashboard($request->user());

        return ApiResponse::success(
            data: new StaffDashboardResource($data),
            message: 'Dashboard staff berhasil diambil.',
            code: 'STAFF_DASHBOARD_SUCCESS'
        );
    }
}
