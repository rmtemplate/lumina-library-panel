<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BookCatalogResource extends JsonResource {
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,

            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'publisher' => $this->publisher,
            'publish_year' => $this->publish_year !== null ? (int) $this->publish_year : null,
            'language' => $this->language,

            'cover_url' => $this->resolveFileUrl(
                path: $this->cover_path ?? $this->cover_url ?? null,
                isExternal: (bool) ($this->cover_is_external ?? false)
            ),

            'category' => new BookCategoryResource($this->whenLoaded('category')),

            'rating' => [
                'average' => round((float) ($this->rating ?? 0), 2),
                'count' => (int) ($this->rating_count ?? 0),
            ],

            'availability' => [
                'total_items' => (int) ($this->total_items_count ?? 0),
                'available_items' => (int) ($this->available_items_count ?? 0),
                'is_available' => (int) ($this->available_items_count ?? 0) > 0,
                'locations' => $this->resolveAvailabilityLocations(),
            ],

            'analytics' => [
                'views_count' => (int) ($this->views_count ?? 0),
                'borrow_count' => (int) ($this->borrow_count ?? 0),
                'wishlist_count' => (int) ($this->wishlist_count ?? 0),
            ],

            'distance' => [
                'nearest_branch_distance_km' => isset($this->nearest_branch_distance_km)
                    ? round((float) $this->nearest_branch_distance_km, 2)
                    : null,
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

            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function resolveAvailabilityLocations(): array
    {
        if (! $this->relationLoaded('availableItems')) {
            return [];
        }

        /** @var Collection $items */
        $items = $this->availableItems;

        return $items
            ->filter(fn ($item) => $item->branch !== null)
            ->groupBy('branch_id')
            ->map(function (Collection $branchItems) {
                $firstItem = $branchItems->first();
                $branch = $firstItem?->branch;

                $racks = $branchItems
                    ->filter(fn ($item) => $item->rack !== null)
                    ->groupBy('rack_id')
                    ->map(function (Collection $rackItems) {
                        $rack = $rackItems->first()?->rack;

                        return [
                            'id' => $rack?->id,
                            'code' => $rack?->rack_code,
                            'name' => $rack?->name,
                            'section_name' => $rack?->section_name,
                            'floor_label' => $rack?->floor_label,
                            'area_label' => $rack?->area_label,
                            'available_items' => $rackItems->count(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'branch' => [
                        'id' => $branch?->id,
                        'code' => $branch?->code,
                        'name' => $branch?->name,
                        'address' => $branch?->address,
                        'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
                        'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
                    ],
                    'available_items' => $branchItems->count(),
                    'racks' => $racks,
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

        if ($isExternal || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}