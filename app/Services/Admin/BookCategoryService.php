<?php

namespace App\Services\Admin;

use App\Models\BookCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookCategoryService
{
    public function __construct(private readonly MediaUploadService $media) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return BookCategory::withCount('books')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy($filters['sort'] ?? 'sort_order', ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc')
            ->orderBy('name')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function create(array $data, ?string $actorId = null): BookCategory
    {
        return DB::transaction(function () use ($data, $actorId) {
            $payload = $this->payload($data, $actorId, true);
            return BookCategory::create($payload);
        });
    }

    public function update(BookCategory $category, array $data, ?string $actorId = null): BookCategory
    {
        return DB::transaction(function () use ($category, $data, $actorId) {
            $payload = $this->payload($data, $actorId, false);
            $category->update($payload);
            return $category->fresh();
        });
    }

    public function delete(BookCategory $category, ?string $actorId = null): void
    {
        if ($category->books()->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Category tidak dapat dihapus karena masih digunakan oleh katalog buku.',
            ]);
        }

        DB::transaction(function () use ($category, $actorId) {
            $category->update(['updated_by' => $actorId]);
            $category->delete();
        });
    }

    private function payload(array $data, ?string $actorId, bool $isCreate): array
    {
        $payload = Arr::only($data, [
            'code', 'slug', 'name', 'description', 'icon_name', 'sort_order', 'is_active',
        ]);

        $payload['is_active'] = (bool) ($data['is_active'] ?? false);
        $payload['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $payload[$isCreate ? 'created_by' : 'updated_by'] = $actorId;

        if (! empty($data['image_file'])) {
            $payload['image_path'] = $this->media->store($data['image_file'], 'categories');
            $payload['image_is_external'] = false;
        }

        return $payload;
    }

    private function perPage(array $filters): int
    {
        $value = (int) ($filters['per_page'] ?? 20);
        return in_array($value, [10, 20, 30, 50, 100], true) ? $value : 20;
    }
}
