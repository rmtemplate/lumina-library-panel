<?php

namespace App\Services\Api\Staff\Borrowing;

use App\Models\Borrowing;
use App\Models\BorrowingItem;
use App\Models\BookItem;
use App\Models\User;
use App\Services\Api\Staff\Broadcast\StaffBroadcastService;
use App\Services\Api\Staff\Support\StaffAccessService;
use App\Services\AppSettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Staff-initiated borrowing — the walk-in counter flow where a librarian
 * scans/searches books into a cart, identifies the member, and confirms on
 * the spot. Unlike the member self-checkout flow (CheckoutService), there's
 * no reservation window or pickup QR step: the books are physically handed
 * over right now, so the Borrowing is created directly as ACTIVE.
 */
class StaffBorrowCartService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly StaffBorrowingService $staffBorrowingService,
        private readonly AppSettingService $appSettingService,
        private readonly StaffBroadcastService $staffBroadcastService
    ) {
    }

    public function create(User $staff, string $memberId, array $bookItemIds, ?string $notes = null): array
    {
        return DB::transaction(function () use ($staff, $memberId, $bookItemIds, $notes) {
            $branchId = $this->staffAccessService->branchId($staff);

            $member = User::query()->where('id', $memberId)->lockForUpdate()->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'member_id' => ['Member tidak ditemukan.'],
                ]);
            }

            if (! $member->is_active) {
                throw ValidationException::withMessages([
                    'member_id' => ['Akun member tidak aktif.'],
                ]);
            }

            $maxBorrowBooks = $this->appSettingService->getInt('max_borrow_books', 5);
            if (count($bookItemIds) > $maxBorrowBooks) {
                throw ValidationException::withMessages([
                    'book_item_ids' => ["Maksimal {$maxBorrowBooks} buku per peminjaman."],
                ]);
            }

            $bookItems = BookItem::query()
                ->with('book')
                ->where('branch_id', $branchId)
                ->whereIn('id', $bookItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($bookItems->count() !== count($bookItemIds)) {
                throw ValidationException::withMessages([
                    'book_item_ids' => ['Beberapa buku tidak ditemukan pada cabang staff.'],
                ]);
            }

            foreach ($bookItems as $bookItem) {
                if (! $bookItem->isAvailable()) {
                    throw ValidationException::withMessages([
                        'book_item_ids' => ["Buku '{$bookItem->book?->title}' sedang tidak tersedia."],
                    ]);
                }
            }

            $maxBorrowDays = $this->appSettingService->getInt('max_borrow_days', 14);
            $branchColumn = $this->staffBorrowingService->branchColumn();

            $borrowing = Borrowing::create([
                'borrowing_code' => $this->generateBorrowingCode(),
                'user_id' => $member->id,
                $branchColumn => $branchId,
                'borrow_date' => now()->toDateString(),
                'due_date' => now()->addDays($maxBorrowDays)->toDateString(),
                'status' => Borrowing::ACTIVE,
                'notes' => $notes,
                'approved_by' => $staff->id,
                'approved_at' => now(),
                'pickup_method' => Borrowing::PICKUP_METHOD_STAFF_BYPASS,
                'created_by' => $staff->id,
                'updated_by' => $staff->id,
            ]);

            foreach ($bookItems as $bookItem) {
                BorrowingItem::create([
                    'borrowing_id' => $borrowing->id,
                    'book_id' => $bookItem->book_id,
                    'book_item_id' => $bookItem->id,
                    'status' => BorrowingItem::ACTIVE,
                ]);

                $bookItem->forceFill([
                    'status' => BookItem::BORROWED,
                    'updated_by' => $staff->id,
                    'updated_at' => now(),
                ])->save();
            }

            $detail = $this->staffBorrowingService->findDetail($staff, $borrowing->id);

            $this->staffBroadcastService->borrowSuccess($borrowing, $branchId, $bookItems->count());

            return [
                'borrowing' => $detail,
                'message' => 'Peminjaman berhasil diproses oleh staff.',
            ];
        }, 3);
    }

    private function generateBorrowingCode(): string
    {
        do {
            $code = 'BRW-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5));
        } while (Borrowing::query()->where('borrowing_code', $code)->exists());

        return $code;
    }
}
