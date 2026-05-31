<?php

namespace App\Http\Resources;

use App\Http\Resources\BookCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BookCatalogLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $book = $this->book;
        $branch = $this->branch;

        return [
            /*
             * id tetap book id agar frontend gampang buka detail buku.
             * branch_id dipakai untuk detail lokasi yang dipilih.
             */
            'id' => $this->book_id,
            'book_id' => $this->book_id,
            'branch_id' => $this->branch_id,

            'title' => $book?->title,
            'author' => $book?->author,
            'isbn' => $book?->isbn,
            'publisher' => $book?->publisher,
            'publish_year' => $book?->publish_year !== null ? (int) $book->publish_year : null,
            'language' => $book?->language,
            'description' => $book?->description,

            'cover_url' => $this->resolveFileUrl(
                path: $book?->cover_path ?? $book?->cover_url ?? null,
                isExternal: (bool) ($book?->cover_is_external ?? false)
            ),

            'category' => $book?->relationLoaded('category')
                ? new BookCategoryResource($book->category)
                : null,

            'branch' => [
                'id' => $branch?->id,
                'code' => $branch?->code,
                'name' => $branch?->name,
                'address' => $branch?->address,
                'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
                'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
                'distance_km' => isset($this->distance_km)
                    ? round((float) $this->distance_km, 2)
                    : null,
            ],

            'rating' => [
                'average' => round((float) ($book?->rating ?? 0), 2),
                'count' => (int) ($book?->rating_count ?? 0),
            ],

            'availability' => [
                'total_items' => (int) ($this->total_items_count ?? 0),
                'available_items' => (int) ($this->available_items_count ?? 0),
                'is_available' => (int) ($this->available_items_count ?? 0) > 0,
                'racks' => $this->resolveRacks(),
            ],

            'analytics' => [
                'views_count' => (int) ($this->views_count ?? 0),
                'borrow_count' => (int) ($this->borrow_count ?? 0),
                'wishlist_count' => (int) ($this->wishlist_count ?? 0),
            ],

            'user_context' => [
                'is_wishlisted' => (bool) ($this->is_wishlisted ?? false),
                'has_reviewed' => (bool) ($this->has_reviewed ?? false),
                'my_rating' => isset($this->my_rating) ? (int) $this->my_rating : null,
                'is_recommended' => (bool) ($this->is_recommended ?? false),
                'recommendation_score' => isset($this->recommendation_score)
                    ? (float) $this->recommendation_score
                    : null,
            ],

            'created_at' => $book?->created_at?->toISOString(),
            'updated_at' => $book?->updated_at?->toISOString(),
        ];
    }

    private function resolveRacks(): array
    {
        if (! $this->relationLoaded('locationRacks')) {
            return [];
        }

        /** @var Collection $locationRacks */
        $locationRacks = $this->locationRacks;

        return $locationRacks
            ->map(function ($row) {
                $rack = $row->rack;

                return [
                    'id' => $rack?->id,
                    'code' => $rack?->rack_code,
                    'name' => $rack?->name,
                    'section_name' => $rack?->section_name,
                    'floor_label' => $rack?->floor_label,
                    'area_label' => $rack?->area_label,
                    'available_items' => (int) ($row->available_items_count ?? 0),
                ];
            })
            ->values()
            ->all();
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