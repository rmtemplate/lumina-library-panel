<?php

namespace App\Services\Api\Staff\Report;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\BookStock;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingPayment;
use App\Models\Fine;
use App\Models\Role;
use App\Models\User;
use App\Services\Api\Staff\Borrowing\StaffBorrowingService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Support\Facades\DB;

class StaffReportService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly StaffBorrowingService $staffBorrowingService
    ) {
    }

    public function getReport(User $staff, string $period = 'month'): array
    {
        $branchId = $this->staffAccessService->branchId($staff);
        $branchColumn = $this->staffBorrowingService->branchColumn();
        [$from, $to] = $this->periodRange($period);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'label' => $period],
            'loans' => $this->loanReport($branchId, $branchColumn, $from, $to),
            'returns' => $this->returnReport($branchId, $branchColumn, $from, $to),
            'fines' => $this->fineReport($branchId, $from, $to),
            'books' => $this->bookReport($branchId, $from, $to),
            'payments' => $this->paymentReport($branchColumn, $branchId, $from, $to),
            'members' => $this->memberReport($branchId, $from, $to),
        ];
    }

    /**
     * "Payment Report" — Midtrans charge outcomes in the period, scoped to
     * this branch's borrowings (BorrowingPayment has no branch_id column
     * of its own).
     */
    private function paymentReport(string $branchColumn, string $branchId, $from, $to): array
    {
        $base = BorrowingPayment::query()
            ->whereHas('borrowing', fn ($q) => $q->where($branchColumn, $branchId))
            ->whereBetween('created_at', [$from, $to]);

        $byStatus = (clone $base)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalPaidAmount = (clone $base)->where('status', BorrowingPayment::PAID)->sum('total_amount');

        return [
            'pending' => (int) ($byStatus[BorrowingPayment::PENDING] ?? 0),
            'paid' => (int) ($byStatus[BorrowingPayment::PAID] ?? 0),
            'expired' => (int) ($byStatus[BorrowingPayment::EXPIRED] ?? 0),
            'failed' => (int) ($byStatus[BorrowingPayment::FAILED] ?? 0),
            'cancelled' => (int) ($byStatus[BorrowingPayment::CANCELLED] ?? 0),
            'total_paid_amount' => (float) $totalPaidAmount,
        ];
    }

    /**
     * "Member Report" — new walk-in + self-registrations in the period at
     * this branch, plus a running total.
     */
    private function memberReport(string $branchId, $from, $to): array
    {
        $base = User::query()
            ->whereHas('roles', fn ($q) => $q->where('code', Role::MEMBER))
            ->where('branch_id', $branchId);

        return [
            'new_registrations_in_period' => (clone $base)->whereBetween('created_at', [$from, $to])->count(),
            'total_members' => (clone $base)->count(),
            'active_members' => (clone $base)->where('is_active', true)->count(),
        ];
    }

    private function loanReport(string $branchId, string $branchColumn, $from, $to): array
    {
        $base = Borrowing::query()->where($branchColumn, $branchId);
        $today = now()->toDateString();

        return [
            'total' => (clone $base)->whereBetween('borrow_date', [$from, $to])->count(),
            'active' => (clone $base)->where('status', 'active')->whereDate('due_date', '>=', $today)->count(),
            'overdue' => (clone $base)->where('status', 'active')->whereDate('due_date', '<', $today)->count(),
            'awaiting_pickup' => (clone $base)->where('status', 'awaiting_pickup')->count(),
            'waiting_payment' => (clone $base)->where('status', 'waiting_payment')->count(),
            'returned' => (clone $base)->whereIn('status', ['returned', 'returned_with_fine'])->whereBetween('return_date', [$from, $to])->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    private function returnReport(string $branchId, string $branchColumn, $from, $to): array
    {
        $base = Borrowing::query()
            ->where($branchColumn, $branchId)
            ->whereIn('status', ['returned', 'returned_with_fine'])
            ->whereBetween('return_date', [$from, $to]);

        $onTime = (clone $base)
            ->whereColumn('return_date', '<=', 'due_date')
            ->count();

        $late = (clone $base)
            ->whereColumn('return_date', '>', 'due_date')
            ->count();

        $total = $onTime + $late;

        return [
            'total' => $total,
            'on_time' => $onTime,
            'late' => $late,
            'on_time_pct' => $total > 0 ? round($onTime / $total * 100, 1) : 0,
            'late_pct' => $total > 0 ? round($late / $total * 100, 1) : 0,
        ];
    }

    private function fineReport(string $branchId, $from, $to): array
    {
        $base = Fine::query()->where('branch_id', $branchId);

        $unpaid = (clone $base)->where('status', Fine::UNPAID)->sum('fine_amount');
        $paid = (clone $base)->where('status', Fine::PAID)->whereBetween('paid_at', [$from, $to])->sum('paid_amount');
        $waived = (clone $base)->where('status', Fine::WAIVED)->whereBetween('updated_at', [$from, $to])->sum('fine_amount');
        $totalCreated = (clone $base)->whereBetween('created_at', [$from, $to])->sum('fine_amount');

        return [
            'total_created_in_period' => (float) $totalCreated,
            'total_unpaid' => (float) $unpaid,
            'total_paid_in_period' => (float) $paid,
            'total_waived_in_period' => (float) $waived,
            'count_unpaid' => (clone $base)->where('status', Fine::UNPAID)->count(),
            'count_paid_in_period' => (clone $base)->where('status', Fine::PAID)->whereBetween('paid_at', [$from, $to])->count(),
        ];
    }

    private function bookReport(string $branchId, $from, $to): array
    {
        // Most borrowed books in the period.
        $mostBorrowed = BorrowingItem::query()
            ->select('book_id', DB::raw('COUNT(*) as borrow_count'))
            ->join('borrowings', 'borrowings.id', '=', 'borrowing_items.borrowing_id')
            ->where(function ($q) use ($branchId) {
                $q->where('borrowings.branch_id', $branchId)
                    ->orWhere('borrowings.library_branch_id', $branchId);
            })
            ->whereBetween('borrowings.borrow_date', [$from, $to])
            ->whereNotNull('borrowing_items.book_id')
            ->groupBy('borrowing_items.book_id')
            ->orderByDesc('borrow_count')
            ->limit(10)
            ->get();

        $bookIds = $mostBorrowed->pluck('book_id');
        $books = Book::query()
            ->whereIn('id', $bookIds)
            ->get()
            ->keyBy('id');

        $mostBorrowedList = $mostBorrowed->map(fn ($row) => [
            'book_id' => $row->book_id,
            'title' => $books[$row->book_id]?->title,
            'author' => $books[$row->book_id]?->author,
            'borrow_count' => (int) $row->borrow_count,
        ])->values()->all();

        // Stock summary per book at this branch.
        $stockSummary = BookStock::query()
            ->select(DB::raw('SUM(total_stock) as total, SUM(available_stock) as available'))
            ->where('library_branch_id', $branchId)
            ->first();

        $bookItemCounts = BookItem::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('branch_id', $branchId)
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return [
            'most_borrowed' => $mostBorrowedList,
            'stock_summary' => [
                'total_stock' => (int) ($stockSummary?->total ?? 0),
                'available_stock' => (int) ($stockSummary?->available ?? 0),
            ],
            'book_item_status' => [
                'available' => (int) ($bookItemCounts['available'] ?? 0),
                'borrowed' => (int) ($bookItemCounts['borrowed'] ?? 0),
                'reserved' => (int) ($bookItemCounts['reserved'] ?? 0),
                'lost' => (int) ($bookItemCounts['lost'] ?? 0),
                'damaged' => (int) ($bookItemCounts['damaged'] ?? 0),
                'maintenance' => (int) ($bookItemCounts['maintenance'] ?? 0),
            ],
        ];
    }

    private function periodRange(string $period): array
    {
        return match ($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()], // month
        };
    }
}
