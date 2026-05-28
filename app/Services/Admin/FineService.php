<?php

namespace App\Services\Admin;

use App\Models\Fine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FineService
{
    public function __construct(private readonly BranchAccessService $branches) {}

    public function paginate(array $filters = [], $actor = null): LengthAwarePaginator
    {
        $query = Fine::with(['user', 'branch', 'borrowingItem.bookItem.book', 'payments']);
        $this->branches->scope($query, $actor, 'branch_id');
        return $query->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->whereHas('user', fn ($user) => $user->where('fullname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('created_at')->paginate($this->perPage($filters))->withQueryString();
    }

    public function summary($actor = null): array
    {
        $base = Fine::query();
        $this->branches->scope($base, $actor, 'branch_id');
        return [
            'unpaid' => (clone $base)->where('status', Fine::UNPAID)->sum('fine_amount'),
            'paid' => (clone $base)->where('status', Fine::PAID)->sum('paid_amount'),
            'waived' => (clone $base)->where('status', Fine::WAIVED)->sum('fine_amount'),
        ];
    }

    public function waive(Fine $fine, string $reason, $actor = null): Fine
    {
        $this->branches->assertCanAccessBranch($actor, $fine->branch_id);
        if ($fine->status !== Fine::UNPAID) throw ValidationException::withMessages(['fine' => 'Hanya denda unpaid yang dapat dibebaskan.']);
        return DB::transaction(function () use ($fine, $reason, $actor) {
            $fine->update(['status' => Fine::WAIVED, 'waived_reason' => $reason, 'updated_by' => $actor?->id]);
            return $fine->fresh(['user', 'borrowingItem.bookItem.book']);
        });
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        return in_array($perPage, [10, 20, 30, 50, 100], true) ? $perPage : 20;
    }
}
