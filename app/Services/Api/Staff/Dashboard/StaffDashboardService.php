<?php

namespace App\Services\Api\Staff\Dashboard;

use App\Models\Borrowing;
use App\Models\Fine;
use App\Models\User;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Support\Facades\Schema;

class StaffDashboardService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly StaffBorrowingService $staffBorrowingService
    ) {
    }

    public function getDashboard(User $staff): array
    {
        $staff = $this->staffAccessService->assertStaff($staff);
        $branch = $this->staffAccessService->branch($staff);
        $branchColumn = $this->staffBorrowingService->branchColumn();
        $today = now()->toDateString();

        $awaitingPickupCount = Borrowing::query()
            ->where($branchColumn, $branch->id)
            ->where('status', 'awaiting_pickup')
            ->count();

        $activeCount = Borrowing::query()
            ->where($branchColumn, $branch->id)
            ->where('status', 'active')
            ->whereDate('due_date', '>=', $today)
            ->count();

        $overdueCount = Borrowing::query()
            ->where($branchColumn, $branch->id)
            ->where('status', 'active')
            ->whereDate('due_date', '<', $today)
            ->count();

        $returnedTodayCount = Borrowing::query()
            ->where($branchColumn, $branch->id)
            ->whereIn('status', ['returned', 'returned_with_fine'])
            ->whereDate('return_date', $today)
            ->count();

        $unpaidFinesCount = Schema::hasTable('fines')
            ? Fine::query()->where('branch_id', $branch->id)->where('status', Fine::UNPAID)->count()
            : 0;

        $recentBorrowings = $this->staffBorrowingService->baseQuery($branch->id)
            ->whereIn('status', ['awaiting_pickup', 'active', 'returned', 'returned_with_fine'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $this->staffBorrowingService->attachComputedFields($recentBorrowings);
        $this->staffBorrowingService->attachLatestQr($recentBorrowings);

        return [
            'staff' => [
                'id' => $staff->id,
                'fullname' => $staff->fullname,
                'email' => $staff->email,
                'role' => $staff->roles->firstWhere('code', 'staff') ? [
                    'code' => 'staff',
                    'name' => $staff->roles->firstWhere('code', 'staff')->name,
                ] : null,
            ],
            'branch' => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'address' => $branch->address,
            ],
            'summary' => [
                'awaiting_pickup_count' => $awaitingPickupCount,
                'active_count' => $activeCount,
                'overdue_count' => $overdueCount,
                'returned_today_count' => $returnedTodayCount,
                'unpaid_fines_count' => $unpaidFinesCount,
            ],
            'recent_borrowings' => $recentBorrowings,
        ];
    }
}
