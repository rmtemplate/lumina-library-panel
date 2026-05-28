<?php

namespace App\Services\Admin;

use App\Enums\BookItemStatus;
use App\Models\BookItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookItemService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function paginate(array $filters = [], $actor = null): LengthAwarePaginator
    {
        $query = BookItem::with(['book.category', 'branch', 'rack']);
        $this->branches->scope($query, $actor, 'branch_id');

        return $query
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('item_code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('book', fn ($book) => $book->where('title', 'like', "%{$search}%")->orWhere('author', 'like', "%{$search}%"));
                });
            })
            ->when($filters['branch_id'] ?? null, fn ($query, string $branchId) => $query->where('branch_id', $this->branches->normalizeBranchId($actor, $branchId)))
            ->when($filters['book_id'] ?? null, fn ($query, string $bookId) => $query->where('book_id', $bookId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy($filters['sort'] ?? 'item_code', ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function create(array $data, $actor): BookItem
    {
        return DB::transaction(function () use ($data, $actor) {
            $branchId = $this->branches->requireBranchId($actor, $data['branch_id'] ?? null);
            $payload = $this->payload($data, $actor?->id);
            $payload['branch_id'] = $branchId;
            $item = BookItem::create($payload);
            return $item->load(['book', 'branch', 'rack']);
        });
    }

    public function update(BookItem $item, array $data, $actor): BookItem
    {
        $this->branches->assertCanAccessBranch($actor, $item->branch_id);

        return DB::transaction(function () use ($item, $data, $actor) {
            $payload = $this->payload($data, $actor?->id, true);
            if (array_key_exists('branch_id', $data)) {
                $payload['branch_id'] = $this->branches->requireBranchId($actor, $data['branch_id']);
            }
            $item->update($payload);
            return $item->fresh(['book', 'branch', 'rack']);
        });
    }

    public function delete(BookItem $item, $actor): void
    {
        $this->branches->assertCanAccessBranch($actor, $item->branch_id);

        if ($item->status === BookItemStatus::BORROWED->value) {
            throw ValidationException::withMessages(['book_item' => 'Copy buku tidak dapat dihapus karena sedang dipinjam.']);
        }

        DB::transaction(function () use ($item, $actor) {
            $item->update(['updated_by' => $actor?->id]);
            $item->delete();
        });
    }

    private function payload(array $data, ?string $actorId = null, bool $updating = false): array
    {
        $payload = collect($data)->only([
            'book_id', 'rack_id', 'item_code', 'barcode', 'acquisition_date', 'source',
            'condition_note', 'status', 'is_active',
        ])->all();

        $payload['status'] = $payload['status'] ?? BookItemStatus::AVAILABLE->value;
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload[$updating ? 'updated_by' : 'created_by'] = $actorId;

        return $payload;
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        return in_array($perPage, [10, 20, 30, 50, 100], true) ? $perPage : 20;
    }
}
