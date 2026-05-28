<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\Fine;
use App\Models\LibraryBranch;
use App\Models\Role;
use App\Models\User;

class DashboardService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function data(?User $actor = null): array
    {
        $branchId = $this->branches->branchId($actor);

        $bookItemQuery = BookItem::query();
        $borrowingQuery = Borrowing::query();
        $fineQuery = Fine::query();
        $userQuery = User::query();
        $logQuery = ActivityLog::query();

        $this->branches->scope($bookItemQuery, $actor, 'branch_id');
        $this->branches->scope($borrowingQuery, $actor, 'branch_id');
        $this->branches->scope($fineQuery, $actor, 'branch_id');
        $this->branches->scope($userQuery, $actor, 'branch_id');
        $this->branches->scope($logQuery, $actor, 'branch_id');

        $itemStatus = (clone $bookItemQuery)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $borrowingStatus = (clone $borrowingQuery)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'scopeLabel' => $actor?->isSuperAdmin() ? 'Global semua branch' : ($actor?->branch?->name ?? 'Branch belum diset'),
            'branches' => $this->branches->visibleBranches($actor),
            'stats' => [
                'total_branches' => $actor?->isSuperAdmin() ? LibraryBranch::count() : 1,
                'total_titles' => Book::count(),
                'total_items' => (clone $bookItemQuery)->count(),
                'available_items' => (clone $bookItemQuery)->where('status', BookItem::AVAILABLE)->count(),
                'borrowed_items' => (clone $bookItemQuery)->where('status', BookItem::BORROWED)->count(),
                'members' => (clone $userQuery)->whereHas('roles', fn ($q) => $q->where('code', Role::MEMBER))->count(),
                'staff' => (clone $userQuery)->whereHas('roles', fn ($q) => $q->where('code', Role::STAFF))->count(),
                'pending_borrowings' => (clone $borrowingQuery)->where('status', Borrowing::PENDING)->count(),
                'active_borrowings' => (clone $borrowingQuery)->where('status', Borrowing::ACTIVE)->count(),
                'overdue_borrowings' => (clone $borrowingQuery)->where('status', Borrowing::OVERDUE)->count(),
                'unpaid_fines' => (clone $fineQuery)->where('status', Fine::UNPAID)->sum('fine_amount'),
            ],
            'charts' => [
                'itemStatus' => [
                    'labels' => ['Available', 'Borrowed', 'Lost', 'Damaged', 'Maintenance'],
                    'data' => [
                        (int) ($itemStatus['available'] ?? 0),
                        (int) ($itemStatus['borrowed'] ?? 0),
                        (int) ($itemStatus['lost'] ?? 0),
                        (int) ($itemStatus['damaged'] ?? 0),
                        (int) ($itemStatus['maintenance'] ?? 0),
                    ],
                ],
                'borrowingStatus' => [
                    'labels' => ['Pending', 'Active', 'Returned', 'Overdue', 'Cancelled'],
                    'data' => [
                        (int) ($borrowingStatus['pending'] ?? 0),
                        (int) ($borrowingStatus['active'] ?? 0),
                        (int) ($borrowingStatus['returned'] ?? 0),
                        (int) ($borrowingStatus['overdue'] ?? 0),
                        (int) ($borrowingStatus['cancelled'] ?? 0),
                    ],
                ],
            ],
            'branchSummaries' => LibraryBranch::withCount(['bookItems', 'racks', 'users', 'borrowings'])
                ->when($actor && ! $actor->isSuperAdmin(), fn ($q) => $q->where('id', $branchId))
                ->orderBy('name')->limit(8)->get(),
            'recentBorrowings' => (clone $borrowingQuery)->with(['user', 'branch', 'items.bookItem.book'])
                ->orderByDesc('created_at')->limit(8)->get(),
            'recentUsers' => (clone $userQuery)->with(['roles', 'branch'])->orderByDesc('created_at')->limit(8)->get(),
            'recentActivities' => (clone $logQuery)->with(['user', 'branch'])->orderByDesc('created_at')->limit(8)->get(),
        ];
    }
}
