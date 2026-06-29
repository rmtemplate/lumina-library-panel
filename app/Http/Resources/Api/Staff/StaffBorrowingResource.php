<?php

namespace App\Http\Resources\Api\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffBorrowingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $branch = $this->relationLoaded('branch') ? $this->branch : ($this->relationLoaded('libraryBranch') ? $this->libraryBranch : null);
        $user = $this->user;

        return [
            'id' => $this->id,
            'borrowing_code' => $this->borrowing_code,
            'raw_status' => $this->status,
            'computed_status' => $this->computed_status ?? $this->status,
            'borrow_date' => $this->borrow_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
            'reservation_expires_at' => $this->reservation_expires_at?->toISOString(),
            'days_remaining' => (int) ($this->days_remaining ?? 0),
            'overdue_days' => (int) ($this->overdue_days ?? 0),
            'fine_per_day' => (float) ($this->fine_per_day ?? 0),
            'total_fine' => (float) ($this->total_fine ?? 0),
            'member' => [
                'id' => $user?->id,
                'fullname' => $user?->fullname,
                'email' => $user?->email,
                'phone' => $user?->phone,
                'member_code' => $user?->member_code,
                'avatar_url' => $user?->avatar_url,
            ],
            'branch' => [
                'id' => $branch?->id,
                'code' => $branch?->code,
                'name' => $branch?->name,
                'address' => $branch?->address,
            ],
            'items_count' => $this->relationLoaded('items') ? $this->items->count() : 0,
            'latest_qr' => $this->latest_qr,
            'actions' => [
                'can_scan_pickup' => ($this->computed_status ?? $this->status) === 'awaiting_pickup',
                'can_scan_return' => in_array(($this->computed_status ?? $this->status), ['active', 'overdue'], true),
                'payment_required' => ($this->computed_status ?? $this->status) === 'waiting_payment'
                    || (float) ($this->total_fine ?? 0) > 0,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
