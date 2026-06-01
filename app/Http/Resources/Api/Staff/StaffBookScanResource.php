<?php

namespace App\Http\Resources\Api\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StaffBookScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $rack = $this->rack;
        $branch = $this->branch;

        return [
            'book_item' => [
                'id' => $this->id,
                'item_code' => $this->item_code,
                'barcode' => $this->barcode,
                'status' => $this->status,
                'is_active' => (bool) $this->is_active,
                'condition_note' => $this->condition_note,
            ],
            'book' => [
                'id' => $book?->id,
                'title' => $book?->title,
                'author' => $book?->author,
                'isbn' => $book?->isbn,
                'publisher' => $book?->publisher,
                'publish_year' => $book?->publish_year !== null ? (int) $book->publish_year : null,
                'cover_url' => $this->resolveFileUrl($book?->cover_path, (bool) ($book?->cover_is_external ?? false)),
                'category' => $book?->category ? [
                    'id' => $book->category->id,
                    'name' => $book->category->name,
                    'slug' => $book->category->slug ?? null,
                ] : null,
            ],
            'branch' => [
                'id' => $branch?->id,
                'code' => $branch?->code,
                'name' => $branch?->name,
                'address' => $branch?->address,
            ],
            'rack' => $rack ? [
                'id' => $rack->id,
                'code' => $rack->rack_code,
                'name' => $rack->name,
                'section_name' => $rack->section_name,
                'floor_label' => $rack->floor_label,
                'area_label' => $rack->area_label,
                'aisle_code' => $rack->aisle_code,
            ] : null,
            'actions' => [
                'can_be_borrowed' => $this->status === 'available' && (bool) $this->is_active,
                'can_be_returned' => $this->status === 'borrowed',
                'needs_attention' => in_array($this->status, ['damaged', 'lost', 'maintenance'], true),
            ],
        ];
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
