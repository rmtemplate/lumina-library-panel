<?php

namespace App\Services\Api\Staff\Rack;

use App\Models\BookItem;
use App\Models\BookMoveHistory;
use App\Models\BookRackAssignment;
use App\Models\Rack;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Api\Staff\Support\StaffAccessService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffRackService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * List all racks belonging to the staff's branch.
     */
    public function listRacks(User $staff): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        return Rack::query()
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('rack_code')
            ->get()
            ->map(fn (Rack $rack) => $this->formatRack($rack))
            ->values()
            ->all();
    }

    /**
     * Get a book item's current rack and assignment info.
     */
    public function getBookItemRack(User $staff, string $bookItemId): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        $bookItem = BookItem::query()
            ->with(['book.category', 'rack', 'branch'])
            ->where('id', $bookItemId)
            ->where('branch_id', $branchId)
            ->first();

        if (! $bookItem) {
            throw ValidationException::withMessages([
                'book_item_id' => ['Buku tidak ditemukan pada cabang Anda.'],
            ]);
        }

        $activeAssignment = BookRackAssignment::query()
            ->with('rack')
            ->where('book_id', $bookItem->book_id)
            ->where('book_item_id', $bookItemId)
            ->where('is_active', true)
            ->latest()
            ->first();

        return [
            'book_item' => $this->formatBookItem($bookItem),
            'current_rack' => $bookItem->rack ? $this->formatRack($bookItem->rack) : null,
            'active_assignment' => $activeAssignment ? [
                'id' => $activeAssignment->id,
                'catalog_code' => $activeAssignment->catalog_code,
                'assigned_at' => $activeAssignment->assigned_at?->toISOString(),
                'rack' => $activeAssignment->rack ? $this->formatRack($activeAssignment->rack) : null,
            ] : null,
        ];
    }

    /**
     * Move a book item to a new rack.
     * Updates BookItem.rack_id, deactivates old assignment, creates new assignment and history.
     */
    public function moveBookItem(User $staff, string $bookItemId, string $targetRackId, string $reason, ?string $notes): array
    {
        return DB::transaction(function () use ($staff, $bookItemId, $targetRackId, $reason, $notes) {
            $branchId = $this->staffAccessService->branchId($staff);

            $bookItem = BookItem::query()
                ->with(['book', 'rack'])
                ->where('id', $bookItemId)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $bookItem) {
                throw ValidationException::withMessages([
                    'book_item_id' => ['Buku tidak ditemukan pada cabang Anda.'],
                ]);
            }

            $targetRack = Rack::query()
                ->where('id', $targetRackId)
                ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
                ->where('is_active', true)
                ->first();

            if (! $targetRack) {
                throw ValidationException::withMessages([
                    'target_rack_id' => ['Rak tujuan tidak ditemukan pada cabang Anda.'],
                ]);
            }

            $sourceRackId = $bookItem->rack_id;

            if ((string) $sourceRackId === (string) $targetRackId) {
                throw ValidationException::withMessages([
                    'target_rack_id' => ['Buku sudah berada di rak yang dipilih.'],
                ]);
            }

            // Deactivate all existing active assignments for this book_item.
            BookRackAssignment::query()
                ->where('book_item_id', $bookItemId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_by' => $staff->id,
                    'updated_at' => now(),
                ]);

            // Create new assignment.
            $assignment = BookRackAssignment::create([
                'book_id' => $bookItem->book_id,
                'book_item_id' => $bookItemId,
                'rack_id' => $targetRackId,
                'catalog_code' => $bookItem->book?->isbn ?? $bookItem->item_code ?? '',
                'assigned_at' => now(),
                'is_active' => true,
                'created_by' => $staff->id,
                'updated_by' => $staff->id,
            ]);

            // Update book item's rack.
            $bookItem->forceFill([
                'rack_id' => $targetRackId,
                'updated_by' => $staff->id,
                'updated_at' => now(),
            ])->save();

            // Record move history.
            BookMoveHistory::create([
                'book_id' => $bookItem->book_id,
                'book_item_id' => $bookItemId,
                'source_rack_id' => $sourceRackId,
                'destination_rack_id' => $targetRackId,
                'reason' => $reason,
                'notes' => $notes,
                'moved_by' => $staff->id,
                'moved_at' => now(),
            ]);

            $this->activityLogService->logBookMove(
                branchId: $branchId,
                staffId: $staff->id,
                bookId: $bookItem->book_id,
                bookTitle: $bookItem->book?->title ?? 'Buku',
                fromRack: $bookItem->rack?->rack_code ?? $sourceRackId,
                toRack: $targetRack->rack_code
            );

            $bookItem->load('rack');

            return [
                'book_item' => $this->formatBookItem($bookItem),
                'moved_from' => $sourceRackId ? ['id' => $sourceRackId] : null,
                'moved_to' => $this->formatRack($targetRack),
                'assignment_id' => $assignment->id,
                'moved_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get move history for a book item, scoped to the staff's branch.
     */
    public function getMoveHistory(User $staff, string $bookItemId): LengthAwarePaginator
    {
        $branchId = $this->staffAccessService->branchId($staff);

        $bookItem = BookItem::query()
            ->where('id', $bookItemId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        return BookMoveHistory::query()
            ->with(['sourceRack', 'destinationRack', 'movedBy:id,fullname,email'])
            ->where('book_item_id', $bookItem->id)
            ->latest('moved_at')
            ->paginate(20);
    }

    private function formatRack(Rack $rack): array
    {
        return [
            'id' => $rack->id,
            'rack_code' => $rack->rack_code,
            'name' => $rack->name,
            'section_name' => $rack->section_name,
            'floor_label' => $rack->floor_label,
            'area_label' => $rack->area_label,
            'aisle_code' => $rack->aisle_code,
        ];
    }

    private function formatBookItem(BookItem $item): array
    {
        return [
            'id' => $item->id,
            'item_code' => $item->item_code,
            'barcode' => $item->barcode,
            'status' => $item->status,
            'book' => $item->book ? [
                'id' => $item->book->id,
                'title' => $item->book->title,
                'author' => $item->book->author,
                'isbn' => $item->book->isbn,
            ] : null,
            'current_rack' => $item->rack ? $this->formatRack($item->rack) : null,
        ];
    }
}
