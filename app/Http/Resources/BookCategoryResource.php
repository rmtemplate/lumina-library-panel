<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BookCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code ?? null,
            'name' => $this->name,
            'slug' => $this->slug ?? null,
            'description' => $this->description,

            'icon' => [
                'name' => $this->icon_name ?? null,
                'image_url' => $this->resolveFileUrl(
                    path: $this->image_path ?? null,
                    isExternal: (bool) ($this->image_is_external ?? false)
                ),
                'is_external' => (bool) ($this->image_is_external ?? false),
            ],

            'display' => [
                'sort_order' => (int) ($this->sort_order ?? 0),
                'is_active' => (bool) $this->is_active,
            ],

            'stats' => [
                'books_count' => (int) ($this->books_count ?? 0),
                'active_books_count' => (int) ($this->active_books_count ?? 0),
                'available_books_count' => (int) ($this->available_books_count ?? 0),
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
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