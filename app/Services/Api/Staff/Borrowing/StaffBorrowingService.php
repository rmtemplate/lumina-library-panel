<?php

namespace App\Services\Api\Staff\Borrowing;

use App\Models\Borrowing;
use App\Models\BorrowingQrCode;
use App\Models\User;
use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use App\Services\Api\Staff\Support\StaffAccessService;
use App\Services\AppSettingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StaffBorrowingService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly AppSettingService $appSettingService,
        private readonly BorrowingReservationExpiryService $reservationExpiryService
    ) {
    }

    public function paginate(User $staff, array $filters = []): LengthAwarePaginator
    {
        // Runtime expiry backstop — see MyBookService::paginate() for rationale.
        $this->reservationExpiryService->expireDueReservations(200);

        $branchId = $this->staffAccessService->branchId($staff);
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 50);

        $query = $this->baseQuery($branchId);
        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applySearch($query, trim((string) ($filters['search'] ?? '')));

        $paginator = $query
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $this->attachComputedFields($paginator->getCollection());
        $this->attachLatestQr($paginator->getCollection());

        return $paginator;
    }

    public function findDetail(User $staff, string $borrowingId): Borrowing
    {
        // Targeted runtime expiry backstop for this single row.
        $this->reservationExpiryService->expireOne($borrowingId);

        $branchId = $this->staffAccessService->branchId($staff);

        $borrowing = $this->baseQuery($branchId)
            ->where('id', $borrowingId)
            ->first();

        if (! $borrowing) {
            throw ValidationException::withMessages([
                'borrowing_id' => ['Data peminjaman tidak ditemukan pada cabang staff.'],
            ]);
        }

        $collection = collect([$borrowing]);
        $this->attachComputedFields($collection);
        $this->attachLatestQr($collection);

        return $borrowing;
    }

    public function baseQuery(string $branchId): Builder
    {
        $branchColumn = $this->branchColumn();

        return Borrowing::query()
            ->with([
                'user.roles',
                'branch',
                'libraryBranch',
                'items.book.category',
                'items.bookItem.rack',
                'items.bookItem.branch',
            ])
            ->where($branchColumn, $branchId);
    }

    public function applyStatusFilter(Builder $query, ?string $status): void
    {
        $today = now()->toDateString();

        match ($status) {
            'awaiting_pickup' => $query->where('status', 'awaiting_pickup'),
            'active' => $query->where('status', 'active')->whereDate('due_date', '>=', $today),
            'overdue' => $query->where('status', 'active')->whereDate('due_date', '<', $today),
            'waiting_payment' => $query->where('status', 'waiting_payment'),
            'returned' => $query->whereIn('status', ['returned', 'returned_with_fine']),
            default => $query->whereIn('status', [
                'awaiting_pickup', 'active', 'waiting_payment', 'returned', 'returned_with_fine',
            ]),
        };
    }

    public function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $keyword = '%' . addcslashes($search, '\\%_') . '%';

        $query->where(function (Builder $query) use ($keyword) {
            $query
                ->where('borrowing_code', 'like', $keyword)
                ->orWhereHas('user', function (Builder $query) use ($keyword) {
                    $query
                        ->where('fullname', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword)
                        ->orWhere('member_code', 'like', $keyword);
                });
        });
    }

    public function attachComputedFields(Collection $borrowings): void
    {
        $today = now()->startOfDay();
        $finePerDay = $this->finePerDay();

        $borrowings->each(function (Borrowing $borrowing) use ($today, $finePerDay) {
            $dueDate = $borrowing->due_date ? Carbon::parse($borrowing->due_date)->startOfDay() : $today;
            $rawDaysRemaining = (int) $today->diffInDays($dueDate, false);
            $daysRemaining = max(0, $rawDaysRemaining);
            $overdueDays = $rawDaysRemaining < 0 ? abs($rawDaysRemaining) : 0;
            $activeItemsCount = $borrowing->relationLoaded('items')
                ? max($borrowing->items->whereIn('status', ['active', 'pending'])->count(), 1)
                : 1;

            $computedStatus = $this->computeStatus((string) $borrowing->status, $overdueDays);
            $estimatedFine = $computedStatus === 'overdue'
                ? $overdueDays * $finePerDay * $activeItemsCount
                : 0;

            $borrowing->setAttribute('computed_status', $computedStatus);
            $borrowing->setAttribute('days_remaining', $daysRemaining);
            $borrowing->setAttribute('overdue_days', $overdueDays);
            $borrowing->setAttribute('estimated_fine', $estimatedFine);
            $borrowing->setAttribute('fine_per_day', $finePerDay);
        });
    }

    public function attachLatestQr(Collection $borrowings): void
    {
        if ($borrowings->isEmpty()) {
            return;
        }

        $qrRows = BorrowingQrCode::query()
            ->whereIn('borrowing_id', $borrowings->pluck('id')->all())
            ->latest('created_at')
            ->get()
            ->groupBy('borrowing_id');

        $borrowings->each(function (Borrowing $borrowing) use ($qrRows) {
            $qr = $qrRows->get($borrowing->id, collect())->first();
            $borrowing->setAttribute('latest_qr', $qr ? [
                'id' => $qr->id,
                'qr_type' => $qr->qr_type,
                'status' => $qr->status,
                'expires_at' => $qr->expires_at?->toISOString(),
                'used_at' => $qr->used_at?->toISOString(),
            ] : null);
        });
    }

    public function branchColumn(): string
    {
        return Schema::hasColumn('borrowings', 'branch_id') ? 'branch_id' : 'library_branch_id';
    }

    private function computeStatus(string $status, int $overdueDays): string
    {
        if ($status === 'active' && $overdueDays > 0) {
            return 'overdue';
        }

        if (in_array($status, ['returned', 'returned_with_fine'], true)) {
            return 'returned';
        }

        return $status; // covers 'waiting_payment', 'awaiting_pickup', etc.
    }

    private function finePerDay(): float
    {
        return $this->appSettingService->getDecimal('fine_per_day', 1000);
    }
}
