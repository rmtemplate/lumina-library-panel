<?php

namespace App\Services\Admin;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BookService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        return Book::with(['category'])
            ->withCount([
                'items as total_items_count',
                'items as available_items_count' => fn ($q) => $q->where('status', 'available')->where('is_active', true),
                'items as borrowed_items_count' => fn ($q) => $q->where('status', 'borrowed'),
                'items as lost_items_count' => fn ($q) => $q->where('status', 'lost'),
            ])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%"));
            })
            ->when($filters['category_id'] ?? null, fn ($query, string $categoryId) => $query->where('category_id', $categoryId))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy($filters['sort'] ?? 'title', ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc')
            ->paginate($this->perPage($filters))
            ->withQueryString();
    }

    public function create(array $data, ?string $actorId = null): Book
    {
        return DB::transaction(function () use ($data, $actorId) {
            $book = Book::create($this->bookPayload($data, $actorId));
            return $book->load('category');
        });
    }

    public function update(Book $book, array $data, ?string $actorId = null): Book
    {
        return DB::transaction(function () use ($book, $data, $actorId) {
            $payload = $this->bookPayload($data, $actorId, true);
            $book->update($payload);
            return $book->fresh('category');
        });
    }

    public function delete(Book $book, ?string $actorId = null): void
    {
        if ($book->items()->where('status', 'borrowed')->exists()) {
            throw ValidationException::withMessages(['book' => 'Buku tidak dapat dihapus karena masih memiliki copy yang sedang dipinjam.']);
        }

        DB::transaction(function () use ($book, $actorId) {
            $book->update(['updated_by' => $actorId]);
            $book->delete();
        });
    }

    public function toggleStatus(Book $book, ?string $actorId = null): Book
    {
        $book->update(['is_active' => ! $book->is_active, 'updated_by' => $actorId]);
        return $book->fresh();
    }

    private function bookPayload(array $data, ?string $actorId = null, bool $updating = false): array
    {
        $payload = Arr::only($data, [
            'title', 'author', 'isbn', 'publisher', 'publish_year', 'description',
            'category_id', 'language', 'page_count', 'edition', 'is_active',
        ]);

        if (($data['cover_image'] ?? null) instanceof UploadedFile) {
            $payload['cover_path'] = $data['cover_image']->store('books/covers', 'public');
            $payload['cover_is_external'] = false;
        } elseif (! empty($data['cover_path']) && ! empty($data['cover_is_external'])) {
            // Seeder/dummy-data only. Admin forms should upload files.
            $payload['cover_path'] = $data['cover_path'];
            $payload['cover_is_external'] = true;
        }

        $payload['language'] = $payload['language'] ?? 'Indonesia';
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload[$updating ? 'updated_by' : 'created_by'] = $actorId;

        return $payload;
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        return in_array($perPage, [10, 20, 30, 50, 100], true) ? $perPage : 20;
    }
}
