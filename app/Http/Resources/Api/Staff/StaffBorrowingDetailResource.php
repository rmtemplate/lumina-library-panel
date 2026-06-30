<?php

namespace App\Http\Resources\Api\Staff;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffBorrowingDetailResource extends StaffBorrowingResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // ── Extended member info ──────────────────────────────────────────
        $user = $this->user;
        $data['member'] = array_merge($data['member'], [
            'institution_name'  => $user?->institution_name,
            'major_name'        => $user?->major_name,
            'current_level'     => $user?->current_level,
            'gender'            => $user?->gender,
            'address'           => $user?->address,
            'is_active'         => (bool) ($user?->is_active ?? false),
            'is_verified'       => (bool) ($user?->is_verified ?? false),
            'member_since'      => $user?->created_at?->toDateString(),
            'total_borrowings'  => $user ? $user->borrowings()->count() : 0,
            'active_borrowings' => $user
                ? $user->borrowings()
                    ->whereIn('status', ['active', 'ready_for_return', 'awaiting_pickup', 'waiting_payment'])
                    ->count()
                : 0,
        ]);

        // ── Items with branch info ────────────────────────────────────────
        $data['items'] = $this->relationLoaded('items')
            ? $this->items->map(fn ($item) => $this->formatItem($item))->values()
            : [];

        // ── Latest payment summary ────────────────────────────────────────
        $payment = $this->relationLoaded('payments') && $this->payments->isNotEmpty()
            ? $this->payments->sortByDesc('created_at')->first()
            : null;

        $data['payment'] = $payment ? [
            'id'             => $payment->id,
            'status'         => $payment->status,
            'amount'         => (float) ($payment->amount ?? 0),
            'payment_method' => $payment->payment_method,
            'paid_at'        => $payment->paid_at?->toISOString(),
        ] : null;

        // ── Operational fields ────────────────────────────────────────────
        $data['pickup_method']    = $this->pickup_method;
        $data['notes']            = $this->notes;
        $data['approved_at']      = $this->approved_at?->toISOString();
        $data['approved_by_name'] = $this->relationLoaded('approvedBy')
            ? $this->approvedBy?->fullname
            : null;

        // ── Timeline (1 extra query — acceptable for single-detail view) ──
        $data['timeline'] = ActivityLog::query()
            ->where('reference_id', $this->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($log) => [
                'id'         => $log->id,
                'type'       => $log->activity_type,
                'title'      => $log->title,
                'subtitle'   => $log->subtitle,
                'created_at' => $log->created_at?->toISOString(),
            ])
            ->values();

        return $data;
    }

    private function formatItem(mixed $item): array
    {
        $book     = $item->book;
        $bookItem = $item->bookItem;
        $rack     = $bookItem?->rack;
        $branch   = $bookItem?->branch;

        return [
            'borrowing_item_id' => $item->id,
            'status'            => $item->status,
            'return_date'       => $item->return_date?->toDateString(),
            'return_condition'  => $item->return_condition,
            'notes'             => $item->notes,
            'book' => [
                'id'        => $book?->id,
                'title'     => $book?->title,
                'author'    => $book?->author,
                'isbn'      => $book?->isbn,
                'cover_url' => $this->resolveFileUrl(
                    $book?->cover_path,
                    (bool) ($book?->cover_is_external ?? false)
                ),
                'category' => $book?->category ? [
                    'id'   => $book->category->id,
                    'name' => $book->category->name,
                ] : null,
            ],
            'book_item' => [
                'id'             => $bookItem?->id,
                'item_code'      => $bookItem?->item_code,
                'barcode'        => $bookItem?->barcode,
                'status'         => $bookItem?->status,
                'condition_note' => $bookItem?->condition_note,
            ],
            'rack' => $rack ? [
                'id'           => $rack->id,
                'code'         => $rack->rack_code,
                'name'         => $rack->name,
                'section_name' => $rack->section_name,
                'floor_label'  => $rack->floor_label,
                'area_label'   => $rack->area_label,
            ] : null,
            'branch' => $branch ? [
                'id'   => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
            ] : null,
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
