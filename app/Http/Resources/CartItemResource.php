<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $branch = $this->branch;

        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'book_id' => $this->book_id,
            'branch_id' => $this->branch_id,

            'book' => [
                'id' => $book?->id,
                'title' => $book?->title,
                'author' => $book?->author,
                'isbn' => $book?->isbn,
                'publisher' => $book?->publisher,
                'publish_year' => $book?->publish_year !== null ? (int) $book->publish_year : null,
                'cover_url' => $this->resolveFileUrl(
                    path: $book?->cover_path ?? null,
                    isExternal: (bool) ($book?->cover_is_external ?? false)
                ),
                'category' => $book?->relationLoaded('category') && $book->category
                    ? new BookCategoryResource($book->category)
                    : null,
                'rating' => [
                    'average' => round((float) ($book?->rating ?? 0), 2),
                    'count' => (int) ($book?->rating_count ?? 0),
                ],
            ],

            'branch' => [
                'id' => $branch?->id,
                'code' => $branch?->code,
                'name' => $branch?->name,
                'address' => $branch?->address,
                'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
                'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
            ],

            'availability' => [
                'available_items' => (int) ($this->available_items_count ?? 0),
                'is_available' => (int) ($this->available_items_count ?? 0) > 0,
            ],

            'added_at' => $this->added_at?->toISOString(),
        ];
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