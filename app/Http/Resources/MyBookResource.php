<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MyBookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branch = $this->resolveBranch();

        return [
            'borrowing_id' => $this->id,
            'borrowing_code' => $this->borrowing_code,
            'status' => $this->computed_status ?? $this->status,
            'raw_status' => $this->status,

            'borrow_date' => $this->borrow_date,
            'due_date' => $this->due_date,
            'return_date' => $this->return_date,

            'days_remaining' => (int) ($this->days_remaining ?? 0),
            'overdue_days' => (int) ($this->overdue_days ?? 0),
            'fine_per_day' => (float) ($this->fine_per_day ?? 0),
            'total_fine' => (float) ($this->total_fine ?? 0),

            'branch' => [
                'id' => $branch?->id,
                'code' => $branch?->code,
                'name' => $branch?->name,
                'address' => $branch?->address,
            ],

            'items_count' => $this->whenLoaded('items', fn () => $this->items->count(), 0),

            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $book = $item->book;
                    $bookItem = $item->bookItem;
                    $rack = $bookItem?->rack;

                    return [
                        'borrowing_item_id' => $item->id,
                        'book_id' => $item->book_id,
                        'book_item_id' => $item->book_item_id,
                        'status' => $item->status,

                        'book' => [
                            'id' => $book?->id,
                            'title' => $book?->title,
                            'author' => $book?->author,
                            'isbn' => $book?->isbn,
                            'cover_url' => $this->resolveFileUrl(
                                path: $book?->cover_path ?? null,
                                isExternal: (bool) ($book?->cover_is_external ?? false)
                            ),
                            'category' => $book?->category ? [
                                'id' => $book->category->id,
                                'name' => $book->category->name,
                                'slug' => $book->category->slug ?? null,
                            ] : null,
                        ],

                        'physical_item' => [
                            'id' => $bookItem?->id,
                            'item_code' => $bookItem?->item_code,
                            'barcode' => $bookItem?->barcode,
                            'status' => $bookItem?->status,
                            'rack' => $rack ? [
                                'id' => $rack->id,
                                'code' => $rack->rack_code,
                                'name' => $rack->name,
                                'section_name' => $rack->section_name,
                                'floor_label' => $rack->floor_label,
                                'area_label' => $rack->area_label,
                            ] : null,
                        ],
                    ];
                })->values();
            }),

            'reservation_expires_at' => $this->reservation_expires_at?->toISOString(),

            'actions' => [
                'can_return' => in_array(
                    (string) ($this->computed_status ?? $this->status),
                    ['active', 'due_soon', 'overdue', 'ready_for_return'],
                    true
                ),
                'can_pickup' => (string) ($this->computed_status ?? $this->status) === 'awaiting_pickup',
                'return_qr_endpoint' => route(
                    'api.v1.my-books.return-qr.refresh',
                    ['borrowing' => $this->id],
                    false
                ),
                'pickup_qr_refresh_endpoint' => route(
                    'api.v1.borrowings.qr.refresh',
                    ['borrowing' => $this->id],
                    false
                ),
                'detail_endpoint' => route(
                    'api.v1.my-books.show',
                    ['borrowing' => $this->id],
                    false
                ),
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveBranch()
    {
        if ($this->relationLoaded('branch')) {
            return $this->branch;
        }

        if ($this->relationLoaded('libraryBranch')) {
            return $this->libraryBranch;
        }

        return null;
    }

    private function resolveFileUrl(?string $path, bool $isExternal = false): ?string
    {
        if (! $path) {
            return null;
        }

        if (
            $isExternal
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        return Storage::url($path);
    }
}