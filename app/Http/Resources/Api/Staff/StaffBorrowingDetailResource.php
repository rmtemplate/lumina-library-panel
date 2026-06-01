<?php

namespace App\Http\Resources\Api\Staff;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffBorrowingDetailResource extends StaffBorrowingResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['items'] = $this->relationLoaded('items')
            ? $this->items->map(function ($item) {
                $book = $item->book;
                $bookItem = $item->bookItem;
                $rack = $bookItem?->rack;

                return [
                    'borrowing_item_id' => $item->id,
                    'status' => $item->status,
                    'return_date' => $item->return_date?->toDateString(),
                    'return_condition' => $item->return_condition,
                    'notes' => $item->notes,
                    'book' => [
                        'id' => $book?->id,
                        'title' => $book?->title,
                        'author' => $book?->author,
                        'isbn' => $book?->isbn,
                        'cover_url' => $this->resolveFileUrl($book?->cover_path, (bool) ($book?->cover_is_external ?? false)),
                        'category' => $book?->category ? [
                            'id' => $book->category->id,
                            'name' => $book->category->name,
                            'slug' => $book->category->slug ?? null,
                        ] : null,
                    ],
                    'book_item' => [
                        'id' => $bookItem?->id,
                        'item_code' => $bookItem?->item_code,
                        'barcode' => $bookItem?->barcode,
                        'status' => $bookItem?->status,
                        'condition_note' => $bookItem?->condition_note,
                    ],
                    'rack' => $rack ? [
                        'id' => $rack->id,
                        'code' => $rack->rack_code,
                        'name' => $rack->name,
                        'section_name' => $rack->section_name,
                        'floor_label' => $rack->floor_label,
                        'area_label' => $rack->area_label,
                    ] : null,
                ];
            })->values()
            : [];

        $data['notes'] = $this->notes;
        $data['approved_at'] = $this->approved_at?->toISOString();

        return $data;
    }

    private function resolveFileUrl(?string $path, bool $isExternal = false): ?string
    {
        if (! $path) {
            return null;
        }

        if ($isExternal || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}
