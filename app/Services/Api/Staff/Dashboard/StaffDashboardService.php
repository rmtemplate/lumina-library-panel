<?php

namespace App\Services\Api\Staff\Dashboard;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingPayment;
use App\Models\Fine;
use App\Models\Rack;
use App\Models\User;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $availableBooksCount = BookItem::query()
            ->where('branch_id', $branch->id)
            ->where('status', 'available')
            ->where('is_active', true)
            ->count();

        $registeredMembersCount = User::query()
            ->where(fn ($q) => $q->where('branch_id', $branch->id)->orWhere('staff_library_id', $branch->id))
            ->whereHas('roles', fn ($q) => $q->where('code', 'member'))
            ->where('is_active', true)
            ->count();

        $recentBorrowings = $this->staffBorrowingService->baseQuery($branch->id)
            ->whereIn('status', ['awaiting_pickup', 'active', 'ready_for_return', 'returned', 'returned_with_fine'])
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
                'avatar_url' => $staff->avatar_url,
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
                'available_books_count' => $availableBooksCount,
                'registered_members_count' => $registeredMembersCount,
                'active_count' => $activeCount,
                'today_borrowings_count' => $todayBorrowingsCount,
                'returned_today_count' => $returnedTodayCount,
                'overdue_count' => $overdueCount,
                'pending_payments_count' => $pendingPaymentsCount,
                'awaiting_pickup_count' => $awaitingPickupCount,
                'unpaid_fines_count' => $unpaidFinesCount,
            ],
            'charts' => $this->buildCharts($branch->id, $branchColumn),
            'recent_borrowings' => $recentBorrowings,
            'popular_books' => $this->buildPopularBooks($branch->id),
            'rack_overview' => $this->buildRackOverview($branch->id),
        ];
    }

    /**
     * Top 5 most-borrowed books all-time at this branch, with availability.
     */
    private function buildPopularBooks(string $branchId): array
    {
        $rows = DB::table('borrowing_items')
            ->join('borrowings', 'borrowings.id', '=', 'borrowing_items.borrowing_id')
            ->join('books', 'books.id', '=', 'borrowing_items.book_id')
            ->leftJoin('book_categories', 'book_categories.id', '=', 'books.category_id')
            ->where(function ($q) use ($branchId) {
                $q->where('borrowings.library_branch_id', $branchId)
                  ->orWhere('borrowings.branch_id', $branchId);
            })
            ->whereNull('borrowings.deleted_at')
            ->whereNull('borrowing_items.deleted_at')
            ->select(
                'books.id',
                'books.title',
                'books.author',
                'books.cover_path',
                'books.cover_is_external',
                'book_categories.name as category_name',
                DB::raw('COUNT(*) as borrow_count')
            )
            ->groupBy('books.id', 'books.title', 'books.author', 'books.cover_path', 'books.cover_is_external', 'book_categories.id', 'book_categories.name')
            ->orderByDesc('borrow_count')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) use ($branchId) {
            $availableCount = BookItem::query()
                ->where('book_id', $row->id)
                ->where('branch_id', $branchId)
                ->where('status', 'available')
                ->where('is_active', true)
                ->count();

            $coverUrl = $row->cover_path
                ? ($row->cover_is_external ? $row->cover_path : asset('storage/' . $row->cover_path))
                : null;

            return [
                'id' => $row->id,
                'title' => $row->title,
                'author' => $row->author,
                'category' => $row->category_name,
                'cover_url' => $coverUrl,
                'borrow_count' => (int) $row->borrow_count,
                'available_count' => $availableCount,
            ];
        })->values()->all();
    }

    /**
     * Top 5 active racks by book count at this branch.
     */
    private function buildRackOverview(string $branchId): array
    {
        return Rack::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->withCount([
                'items as total_books_count',
                'items as available_books_count' => fn ($q) => $q->where('status', 'available')->where('is_active', true),
            ])
            ->orderByDesc('total_books_count')
            ->limit(5)
            ->get()
            ->map(fn ($rack) => [
                'id' => $rack->id,
                'rack_code' => $rack->rack_code,
                'name' => $rack->name,
                'floor_label' => $rack->floor_label,
                'capacity' => $rack->capacity,
                'total_books' => $rack->total_books_count,
                'available_books' => $rack->available_books_count,
            ])
            ->values()
            ->all();
    }

    /**
     * Build aggregated chart data for the dashboard — 7-day trend, 6-month
     * monthly breakdown, and top 5 borrowed categories. All queries are
     * simple GROUP BY aggregates; no full-row loading.
     */
    private function buildCharts(string $branchId, string $branchColumn): array
    {
        // 7-day daily borrowing trend
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $dailyRaw = Borrowing::query()
            ->select(DB::raw('DATE(borrow_date) as day, COUNT(*) as count'))
            ->where($branchColumn, $branchId)
            ->where('borrow_date', '>=', $sevenDaysAgo)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');

        $borrowingTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $borrowingTrend[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'count' => (int) ($dailyRaw[$date] ?? 0),
            ];
        }

        // Last 6 months monthly borrowing count
        $sixMonthsAgo = now()->startOfMonth()->subMonths(5);
        $monthlyRaw = Borrowing::query()
            ->select(DB::raw("DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as count"))
            ->where($branchColumn, $branchId)
            ->where('borrow_date', '>=', $sixMonthsAgo)
            ->whereNotIn('status', ['cancelled', 'expired'])
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $monthlyBorrowings = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $key = $m->format('Y-m');
            $monthlyBorrowings[] = [
                'month' => $key,
                'label' => $m->format('M'),
                'count' => (int) ($monthlyRaw[$key] ?? 0),
            ];
        }

        // Top 5 most-borrowed book categories this month
        $monthStart = now()->startOfMonth()->toDateString();
        $topCategories = DB::table('borrowing_items')
            ->join('borrowings', 'borrowings.id', '=', 'borrowing_items.borrowing_id')
            ->join('books', 'books.id', '=', 'borrowing_items.book_id')
            ->join('book_categories', 'book_categories.id', '=', 'books.category_id')
            ->where("borrowings.{$branchColumn}", $branchId)
            ->where('borrowings.borrow_date', '>=', $monthStart)
            ->whereNull('borrowings.deleted_at')
            ->whereNull('borrowing_items.deleted_at')
            ->select('book_categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('book_categories.id', 'book_categories.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'count' => (int) $row->count])
            ->values()
            ->all();

        return [
            'borrowing_trend' => $borrowingTrend,
            'monthly_borrowings' => $monthlyBorrowings,
            'top_categories' => $topCategories,
        ];
    }
}
