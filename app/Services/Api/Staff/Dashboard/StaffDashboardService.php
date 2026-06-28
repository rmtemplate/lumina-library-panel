<?php

namespace App\Services\Api\Staff\Dashboard;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingPayment;
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

        // "Total Books" = physical copies (BookItem) at THIS branch, not
        // the global catalog count — every other stat here is branch-
        // scoped, and inventory size is inherently a per-branch concept
        // (each copy belongs to exactly one branch via BookItem.branch_id).
        $totalBooksCount = BookItem::query()->where('branch_id', $branch->id)->count();

        $todayBorrowingsCount = Borrowing::query()
            ->where($branchColumn, $branch->id)
            ->whereDate('borrow_date', $today)
            ->count();

        // "Pending Payments" = Midtrans charges currently awaiting
        // completion (BorrowingPayment::PENDING), distinct from
        // unpaid_fines_count (denda that may not have a payment attempt
        // in progress yet at all).
        $pendingPaymentsCount = BorrowingPayment::query()
            ->where('status', BorrowingPayment::PENDING)
            ->whereHas('borrowing', fn ($q) => $q->where($branchColumn, $branch->id))
            ->count();

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
                'total_books_count' => $totalBooksCount,
                'active_count' => $activeCount,
                'today_borrowings_count' => $todayBorrowingsCount,
                'returned_today_count' => $returnedTodayCount,
                'overdue_count' => $overdueCount,
                'pending_payments_count' => $pendingPaymentsCount,
                // Kept for back-compat with any existing consumer of this
                // endpoint — distinct from pending_payments_count, see above.
                'awaiting_pickup_count' => $awaitingPickupCount,
                'unpaid_fines_count' => $unpaidFinesCount,
            ],
            'recent_borrowings' => $recentBorrowings,
        ];
    }
}
