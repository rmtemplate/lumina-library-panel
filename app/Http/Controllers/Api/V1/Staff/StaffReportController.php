<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Services\Api\Staff\Report\StaffReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffReportController extends ApiController
{
    public function __construct(
        private readonly StaffReportService $reportService
    ) {
    }

    /**
     * GET /api/v1/staff/reports
     * Comprehensive branch-scoped report.
     *
     * Query params:
     *   period = week | month | year  (default: month)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['sometimes', Rule::in(['week', 'month', 'year'])],
        ]);

        $report = $this->reportService->getReport(
            staff: $request->user(),
            period: $request->query('period', 'month')
        );

        return $this->success(data: $report, message: 'Laporan berhasil diambil.', code: 'REPORT_FOUND');
    }
}
