<?php

namespace App\Http\Resources\Api\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'staff' => $this['staff'] ?? null,
            'branch' => $this['branch'] ?? null,
            'summary' => $this['summary'] ?? [],
            'charts' => $this['charts'] ?? [],
            'recent_borrowings' => StaffBorrowingResource::collection(collect($this['recent_borrowings'] ?? []))->resolve($request),
        ];
    }
}
