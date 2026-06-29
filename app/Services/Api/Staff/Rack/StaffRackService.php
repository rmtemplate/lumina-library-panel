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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffRackService
{
    public function __construct(
        private readonly StaffAccessService $staffAccessService,
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * List all racks belonging to the staff's branch, with book counts.
     */
    public function listRacks(User $staff): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        return Rack::query()
            ->withCount(['items as total_books_count', 'items as available_books_count' => fn ($q) => $q->where('status', 'available')])
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('rack_code')
            ->get()
            ->map(fn (Rack $rack) => array_merge($this->formatRack($rack), [
                'capacity' => $rack->capacity,
                'is_basement' => (bool) $rack->is_basement,
                'notes' => $rack->notes,
                'total_books_count' => (int) $rack->total_books_count,
                'available_books_count' => (int) $rack->available_books_count,
            ]))
            ->values()
            ->all();
    }

    /**
     * Get rack detail with books inside it.
     */
    public function showRack(User $staff, string $rackId): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        $rack = Rack::query()
            ->withCount(['items as total_books_count', 'items as available_books_count' => fn ($q) => $q->where('status', 'available')])
            ->where('id', $rackId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->first();

        if (! $rack) {
            throw ValidationException::withMessages(['rack_id' => ['Rak tidak ditemukan pada cabang Anda.']]);
        }

        $books = BookItem::query()
            ->with('book.category')
            ->where('rack_id', $rackId)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->orderBy('item_code')
            ->get()
            ->map(fn (BookItem $item) => $this->formatBookItem($item))
            ->values()
            ->all();

        $totalCount = (int) $rack->total_books_count;
        $availableCount = (int) $rack->available_books_count;

        return [
            'rack' => array_merge($this->formatRack($rack), [
                'capacity' => $rack->capacity,
                'is_basement' => (bool) $rack->is_basement,
                'notes' => $rack->notes,
                'is_active' => (bool) $rack->is_active,
            ]),
            'stats' => [
                'total_books' => $totalCount,
                'available_books' => $availableCount,
                'borrowed_books' => $totalCount - $availableCount,
                'utilization_pct' => $rack->capacity > 0 ? round($totalCount / $rack->capacity * 100, 1) : 0,
            ],
            'books' => $books,
        ];
    }

    /**
     * Create a new rack in the staff's branch.
     */
    public function createRack(User $staff, array $data): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        // Unique rack_code within branch
        $exists = Rack::query()
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->where('rack_code', $data['rack_code'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['rack_code' => ['Kode rak sudah digunakan di cabang ini.']]);
        }

        $rack = Rack::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $branchId,
            'rack_code' => $data['rack_code'],
            'name' => $data['name'],
            'section_name' => $data['section_name'] ?? null,
            'floor_label' => $data['floor_label'] ?? null,
            'area_label' => $data['area_label'] ?? null,
            'aisle_code' => $data['aisle_code'] ?? null,
            'capacity' => $data['capacity'] ?? 50,
            'is_basement' => $data['is_basement'] ?? false,
            'notes' => $data['notes'] ?? null,
            'is_active' => true,
            'created_by' => $staff->id,
            'updated_by' => $staff->id,
        ]);

        return array_merge($this->formatRack($rack), [
            'capacity' => $rack->capacity,
            'is_basement' => $rack->is_basement,
            'notes' => $rack->notes,
            'total_books_count' => 0,
            'available_books_count' => 0,
        ]);
    }

    /**
     * Update rack metadata.
     */
    public function updateRack(User $staff, string $rackId, array $data): array
    {
        $branchId = $this->staffAccessService->branchId($staff);

        $rack = Rack::query()
            ->where('id', $rackId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->first();

        if (! $rack) {
            throw ValidationException::withMessages(['rack_id' => ['Rak tidak ditemukan pada cabang Anda.']]);
        }

        if (isset($data['rack_code']) && $data['rack_code'] !== $rack->rack_code) {
            $exists = Rack::query()
                ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
                ->where('rack_code', $data['rack_code'])
                ->where('id', '!=', $rackId)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['rack_code' => ['Kode rak sudah digunakan di cabang ini.']]);
            }
        }

        $rack->forceFill(array_filter([
            'rack_code' => $data['rack_code'] ?? $rack->rack_code,
            'name' => $data['name'] ?? $rack->name,
            'section_name' => array_key_exists('section_name', $data) ? $data['section_name'] : $rack->section_name,
            'floor_label' => array_key_exists('floor_label', $data) ? $data['floor_label'] : $rack->floor_label,
            'area_label' => array_key_exists('area_label', $data) ? $data['area_label'] : $rack->area_label,
            'aisle_code' => array_key_exists('aisle_code', $data) ? $data['aisle_code'] : $rack->aisle_code,
            'capacity' => $data['capacity'] ?? $rack->capacity,
            'is_basement' => $data['is_basement'] ?? $rack->is_basement,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $rack->notes,
            'updated_by' => $staff->id,
            'updated_at' => now(),
        ], fn ($v) => $v !== null || true))->save();

        $rack->loadCount(['items as total_books_count', 'items as available_books_count' => fn ($q) => $q->where('status', 'available')]);

        return array_merge($this->formatRack($rack), [
            'capacity' => $rack->capacity,
            'is_basement' => $rack->is_basement,
            'notes' => $rack->notes,
            'total_books_count' => (int) $rack->total_books_count,
            'available_books_count' => (int) $rack->available_books_count,
        ]);
    }

    /**
     * Soft-delete (deactivate) a rack. Prevents deletion if books are assigned.
     */
    public function deleteRack(User $staff, string $rackId): void
    {
        $branchId = $this->staffAccessService->branchId($staff);

        $rack = Rack::query()
            ->where('id', $rackId)
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhere('library_branch_id', $branchId))
            ->first();

        if (! $rack) {
            throw ValidationException::withMessages(['rack_id' => ['Rak tidak ditemukan pada cabang Anda.']]);
        }

        $hasBooks = BookItem::query()
            ->where('rack_id', $rackId)
            ->where('is_active', true)
            ->exists();

        if ($hasBooks) {
            throw ValidationException::withMessages(['rack_id' => ['Rak masih memiliki buku aktif. Pindahkan buku terlebih dahulu sebelum menghapus rak.']]);
        }

        $rack->forceFill(['is_active' => false, 'updated_by' => $staff->id, 'updated_at' => now()])->save();
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
                'book_code' => $item->book->book_code ?? null,
                'cover_url' => $item->book->cover_url,
            ] : null,
            'current_rack' => $item->rack ? $this->formatRack($item->rack) : null,
        ];
    }
}
