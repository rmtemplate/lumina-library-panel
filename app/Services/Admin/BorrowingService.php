<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\BookItem;
use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BorrowingQrCode;
use App\Models\BorrowingStatusHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BorrowingService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function paginate(array $filters = [], $actor = null): LengthAwarePaginator
    {
        $query = Borrowing::with(['user', 'branch', 'items.bookItem.book']);
        $this->branches->scope($query, $actor, 'branch_id');

        return $query
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('borrowing_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('fullname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function approve(Borrowing $borrowing, $actor = null): Borrowing
    {
        $this->branches->assertCanAccessBranch($actor, $borrowing->branch_id);

        if ($borrowing->status !== Borrowing::PENDING) {
            throw ValidationException::withMessages(['borrowing' => 'Hanya peminjaman pending yang dapat disetujui.']);
        }

        return DB::transaction(function () use ($borrowing, $actor) {
            $borrowing->loadMissing(['items.bookItem.book', 'branch']);

            foreach ($borrowing->items as $item) {
                $copy = BookItem::whereKey($item->book_item_id)->lockForUpdate()->first();
                if (! $copy || ! $copy->isAvailable()) {
                    throw ValidationException::withMessages(['stock' => "Copy buku {$item->bookItem?->item_code} tidak tersedia."]);
                }
                $copy->update(['status' => BookItem::BORROWED, 'updated_by' => $actor?->id]);
                $item->update(['status' => BorrowingItem::ACTIVE]);
                $this->writeItemHistory($item, Borrowing::PENDING, BorrowingItem::ACTIVE, $actor?->id, 'Peminjaman disetujui.');
            }

            $borrowing->update(['status' => Borrowing::ACTIVE, 'approved_by' => $actor?->id, 'approved_at' => now(), 'updated_by' => $actor?->id]);
            $this->generateQr($borrowing, BorrowingQrCode::TYPE_RETURN, $actor?->id);
            $this->activity($borrowing, ActivityLog::BORROW, 'Peminjaman disetujui', $actor?->id);

            return $borrowing->fresh(['user', 'branch', 'items.bookItem.book', 'qrCodes']);
        });
    }

    public function cancel(Borrowing $borrowing, $actor = null): Borrowing
    {
        $this->branches->assertCanAccessBranch($actor, $borrowing->branch_id);

        if (! in_array($borrowing->status, [Borrowing::PENDING, Borrowing::ACTIVE], true)) {
            throw ValidationException::withMessages(['borrowing' => 'Peminjaman ini tidak dapat dibatalkan.']);
        }

        return DB::transaction(function () use ($borrowing, $actor) {
            $borrowing->loadMissing(['items.bookItem']);
            foreach ($borrowing->items as $item) {
                $from = $item->status;
                if ($borrowing->status === Borrowing::ACTIVE && $item->bookItem) {
                    $item->bookItem->update(['status' => BookItem::AVAILABLE, 'updated_by' => $actor?->id]);
                }
                $item->update(['status' => BorrowingItem::CANCELLED]);
                $this->writeItemHistory($item, $from, BorrowingItem::CANCELLED, $actor?->id, 'Peminjaman dibatalkan.');
            }
            $borrowing->qrCodes()->update(['status' => BorrowingQrCode::STATUS_REVOKED, 'updated_by' => $actor?->id]);
            $borrowing->update(['status' => Borrowing::CANCELLED, 'updated_by' => $actor?->id]);
            $this->activity($borrowing, ActivityLog::RETURN, 'Peminjaman dibatalkan', $actor?->id);
            return $borrowing->fresh(['user', 'branch', 'items.bookItem.book']);
        });
    }

    public function generateQr(Borrowing $borrowing, string $type, ?string $actorId = null, int $minutes = 60): BorrowingQrCode
    {
        return BorrowingQrCode::create([
            'borrowing_id' => $borrowing->id,
            'qr_code' => strtoupper($type).'-'.Str::upper(Str::random(32)),
            'qr_type' => $type,
            'status' => BorrowingQrCode::STATUS_ACTIVE,
            'payload' => ['borrowing_id' => $borrowing->id, 'borrowing_code' => $borrowing->borrowing_code, 'type' => $type],
            'expires_at' => now()->addMinutes($minutes),
            'created_by' => $actorId,
        ]);
    }

    private function writeItemHistory(BorrowingItem $item, ?string $from, string $to, ?string $actorId, ?string $notes = null): void
    {
        BorrowingStatusHistory::create(['borrowing_item_id' => $item->id, 'from_status' => $from, 'to_status' => $to, 'changed_at' => now(), 'changed_by' => $actorId, 'notes' => $notes]);
    }

    private function activity(Borrowing $borrowing, string $type, string $title, ?string $actorId = null): void
    {
        ActivityLog::create(['branch_id' => $borrowing->branch_id, 'user_id' => $borrowing->user_id, 'activity_type' => $type, 'title' => $title, 'subtitle' => $borrowing->borrowing_code, 'reference_type' => 'borrowing', 'reference_id' => $borrowing->id, 'created_at' => now()]);
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        return in_array($perPage, [10, 20, 30, 50, 100], true) ? $perPage : 20;
    }
}
