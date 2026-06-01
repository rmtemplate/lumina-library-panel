<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Models\ActivityLog;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffActivityLogController extends ApiController
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService
    ) {
    }

    /**
     * GET /api/v1/staff/activity-logs
     * Returns activity logs scoped to the staff's branch only.
     */
    public function index(Request $request): JsonResponse
    {
        $staff = $request->user();
        $branchId = $this->staffAccessService->branchId($staff);

        $perPage = min(max((int) ($request->query('per_page', 20)), 1), 50);
        $type = $request->query('type');

        $query = ActivityLog::query()
            ->with('user:id,fullname,email,member_code')
            ->where('branch_id', $branchId)
            ->when($type, fn ($q) => $q->where('activity_type', $type))
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage)->withQueryString();

        return $this->paginated(
            paginator: $paginator,
            data: $paginator->getCollection()->map(fn (ActivityLog $log) => $this->format($log)),
            message: 'Data aktivitas berhasil diambil.',
            code: 'ACTIVITY_LOGS_FOUND'
        );
    }

    private function format(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'activity_type' => $log->activity_type,
            'title' => $log->title,
            'subtitle' => $log->subtitle,
            'reference_type' => $log->reference_type,
            'reference_id' => $log->reference_id,
            'metadata' => $log->metadata,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'fullname' => $log->user->fullname,
                'email' => $log->user->email,
                'member_code' => $log->user->member_code,
            ] : null,
            'created_at' => $log->created_at?->toISOString(),
        ];
    }
}
