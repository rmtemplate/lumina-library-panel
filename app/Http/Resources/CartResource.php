<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : collect();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'summary' => [
                'total_items' => $items->count(),
                'total_branches' => $items->pluck('branch_id')->unique()->count(),
            ],
            'groups' => $this->resolveGroups($items),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function resolveGroups(Collection $items): array
    {
        return $items
            ->groupBy('branch_id')
            ->map(function (Collection $branchItems) {
                $firstItem = $branchItems->first();
                $branch = $firstItem?->branch;

                return [
                    'branch' => [
                        'id' => $branch?->id,
                        'code' => $branch?->code,
                        'name' => $branch?->name,
                        'address' => $branch?->address,
                        'latitude' => $branch?->latitude !== null ? (float) $branch->latitude : null,
                        'longitude' => $branch?->longitude !== null ? (float) $branch->longitude : null,
                    ],
                    'items_count' => $branchItems->count(),
                    'items' => CartItemResource::collection($branchItems),
                ];
            })
            ->values()
            ->all();
    }
}