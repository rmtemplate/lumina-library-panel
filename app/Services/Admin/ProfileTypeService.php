<?php

namespace App\Services\Admin;

use App\Models\ProfileType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProfileTypeService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return ProfileType::withCount('users')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($sub) => $sub
                    ->where('label', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function create(array $data, ?string $actorId = null): ProfileType
    {
        return DB::transaction(fn () => ProfileType::create($this->payload($data, $actorId, true)));
    }

    public function update(ProfileType $profileType, array $data, ?string $actorId = null): ProfileType
    {
        return DB::transaction(function () use ($profileType, $data, $actorId) {
            $profileType->update($this->payload($data, $actorId, false));
            return $profileType->fresh();
        });
    }

    public function delete(ProfileType $profileType, ?string $actorId = null): void
    {
        if ($profileType->users()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Profile type tidak dapat dihapus karena masih digunakan oleh user/member.',
            ]);
        }

        DB::transaction(function () use ($profileType, $actorId) {
            $profileType->update(['updated_by' => $actorId]);
            $profileType->delete();
        });
    }

    private function payload(array $data, ?string $actorId, bool $isCreate): array
    {
        $payload = Arr::only($data, [
            'code', 'label', 'description', 'institution_label', 'major_label', 'level_label',
            'sort_order', 'is_active', 'show_institution', 'show_major', 'show_level',
            'requires_institution', 'requires_major', 'requires_level',
        ]);

        foreach (['show_institution', 'show_major', 'show_level', 'requires_institution', 'requires_major', 'requires_level', 'is_active'] as $field) {
            $payload[$field] = (bool) ($data[$field] ?? false);
        }
        $payload['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $payload[$isCreate ? 'created_by' : 'updated_by'] = $actorId;

        return $payload;
    }

    private function perPage(array $filters): int
    {
        $value = (int) ($filters['per_page'] ?? 20);
        return in_array($value, [10, 20, 30, 50, 100], true) ? $value : 20;
    }
}
