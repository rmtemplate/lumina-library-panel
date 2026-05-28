<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'category' => $this->category?->name,
            'cover_url' => $this->cover_url,
            'rating' => (float) $this->rating,
            'available_items' => (int) ($this->available_items_count ?? 0),
        ];
    }
}
