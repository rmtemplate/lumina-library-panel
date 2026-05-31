<?php

namespace App\Services\Api\Book;

use App\Models\BookItem;
use App\Models\BookView;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookService
{
    public function getBooks(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $limit = $this->normalizeLimit((int) ($filters['limit'] ?? 12));
        $isRecommendationMode = $this->isRecommendationMode($filters);

        $query = $this->baseBookLocationQuery($user);

        $this->applyFilters($query, $filters);

        if ($user && $isRecommendationMode) {
            $this->applyRecommendationPriority($query, $user);
        }

        $this->applySort($query, $filters, $isRecommendationMode);

        $paginator = $query
            ->paginate($limit)
            ->withQueryString();

        $this->attachRackSummary($paginator->getCollection());

        return $paginator;
    }

    public function getBookDetailByBranch(
        string $bookId,
        string $branchId,
        ?User $user = null,
        array $filters = []
    ): BookItem {
        $query = $this->baseBookLocationQuery($user)
            ->where('book_items.book_id', $bookId)
            ->where('book_items.branch_id', $branchId);

        $this->applyDistance($query, $filters);

        /** @var BookItem|null $row */
        $row = $query->first();

        if (! $row) {
            throw new ModelNotFoundException('Book detail not found for selected branch.');
        }

        $collection = collect([$row]);

        $this->attachRackSummary($collection);
        $this->attachStatusSummary($row);
        $this->recordBookView($bookId, $user);

        return $row;
    }


    public function addBookToWishlist(string $bookId, User $user): bool
    {
        $wishlist = $user->wishlists()
        ->where('book_id', $bookId)
        ->first();

        if ($wishlist) {
            $wishlist->delete();

            return false; // removed from wishlist
        }

        $user->wishlists()->create([
            'book_id' => $bookId,
        ]);

        return true; // added to wishlist
    }


    private function baseBookLocationQuery(?User $user = null): Builder
    {
        $bookViewStats = DB::table('book_views')
            ->select('book_id')
            ->selectRaw('COUNT(*) as views_count')
            ->groupBy('book_id');

        $borrowStats = DB::table('borrowing_items')
            ->select('book_id')
            ->selectRaw('COUNT(*) as borrow_count')
            ->groupBy('book_id');

        $wishlistStats = DB::table('user_wishlists')
            ->select('book_id')
            ->selectRaw('COUNT(*) as wishlist_count')
            ->groupBy('book_id');

        $query = BookItem::query()
            ->select([
                'book_items.book_id',
                'book_items.branch_id',
            ])
            ->selectRaw('MIN(book_items.id) as id')
            ->selectRaw('COUNT(book_items.id) as total_items_count')
            ->selectRaw(
                "SUM(CASE WHEN book_items.status = ? THEN 1 ELSE 0 END) as available_items_count",
                [BookItem::AVAILABLE]
            )
            ->selectRaw('COALESCE(MAX(book_view_stats.views_count), 0) as views_count')
            ->selectRaw('COALESCE(MAX(borrow_stats.borrow_count), 0) as borrow_count')
            ->selectRaw('COALESCE(MAX(wishlist_stats.wishlist_count), 0) as wishlist_count')
            ->join('books', 'books.id', '=', 'book_items.book_id')
            ->join('library_branches', 'library_branches.id', '=', 'book_items.branch_id')
            ->leftJoinSub($bookViewStats, 'book_view_stats', function ($join) {
                $join->on('book_view_stats.book_id', '=', 'book_items.book_id');
            })
            ->leftJoinSub($borrowStats, 'borrow_stats', function ($join) {
                $join->on('borrow_stats.book_id', '=', 'book_items.book_id');
            })
            ->leftJoinSub($wishlistStats, 'wishlist_stats', function ($join) {
                $join->on('wishlist_stats.book_id', '=', 'book_items.book_id');
            })
            ->with([
                'book.category',
                'branch',
            ])
            ->where('books.is_active', true)
            ->where('book_items.is_active', true)
            ->where('library_branches.is_active', true)
            ->groupBy([
                'book_items.book_id',
                'book_items.branch_id',
            ]);

        if ($user) {
            $userWishlistStats = DB::table('user_wishlists')
                ->select('book_id')
                ->selectRaw('1 as is_wishlisted')
                ->where('user_id', $user->id)
                ->groupBy('book_id');

            $userReviewStats = DB::table('book_reviews')
                ->select('book_id')
                ->selectRaw('1 as has_reviewed')
                ->selectRaw('MAX(rating) as my_rating')
                ->where('user_id', $user->id)
                ->whereNull('deleted_at')
                ->groupBy('book_id');

            $query
                ->leftJoinSub($userWishlistStats, 'user_wishlist_stats', function ($join) {
                    $join->on('user_wishlist_stats.book_id', '=', 'book_items.book_id');
                })
                ->leftJoinSub($userReviewStats, 'user_review_stats', function ($join) {
                    $join->on('user_review_stats.book_id', '=', 'book_items.book_id');
                })
                ->selectRaw('COALESCE(MAX(user_wishlist_stats.is_wishlisted), 0) as is_wishlisted')
                ->selectRaw('COALESCE(MAX(user_review_stats.has_reviewed), 0) as has_reviewed')
                ->selectRaw('MAX(user_review_stats.my_rating) as my_rating');
        }

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $keyword = '%' . $this->escapeLike($search) . '%';

            $query->where(function (Builder $query) use ($keyword) {
                $query
                    ->where('books.title', 'like', $keyword)
                    ->orWhere('books.author', 'like', $keyword)
                    ->orWhere('books.isbn', 'like', $keyword)
                    ->orWhere('books.publisher', 'like', $keyword)
                    ->orWhere('books.description', 'like', $keyword);
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('books.category_id', $filters['category_id']);
        }

        if (! empty($filters['category_slug'])) {
            $query
                ->join('book_categories', 'book_categories.id', '=', 'books.category_id')
                ->where('book_categories.slug', $filters['category_slug']);
        }

        if (! empty($filters['branch_id'])) {
            $query->where('book_items.branch_id', $filters['branch_id']);
        }

        if (! empty($filters['year'])) {
            $query->where('books.publish_year', (int) $filters['year']);
        }

        if (! empty($filters['status'])) {
            $query->havingRaw(
                "SUM(CASE WHEN book_items.status = ? THEN 1 ELSE 0 END) > 0",
                [$filters['status']]
            );
        }

        $this->applyDistance($query, $filters);
    }

    private function applyDistance(Builder $query, array $filters): void
    {
        if (empty($filters['latitude']) || empty($filters['longitude'])) {
            return;
        }

        $lat = (float) $filters['latitude'];
        $lng = (float) $filters['longitude'];
        $distanceSql = $this->distanceSql('library_branches');

        $query->selectRaw("{$distanceSql} as distance_km", [$lat, $lng, $lat]);

        if (! empty($filters['max_distance_km'])) {
            $query->havingRaw("distance_km <= ?", [(float) $filters['max_distance_km']]);
        }
    }

    private function applySort(Builder $query, array $filters, bool $isRecommendationMode): void
    {
        $sort = $filters['sort'] ?? null;

        if (! $sort) {
            $sort = $isRecommendationMode ? 'rating' : 'trending';
        }

        match ($sort) {
            'nearest' => ! empty($filters['latitude']) && ! empty($filters['longitude'])
                ? $query->orderBy('distance_km')
                : $query->orderByDesc('books.rating'),

            'rating' => $query
                ->orderByDesc('books.rating')
                ->orderByDesc('books.rating_count'),

            'newest' => $query->orderByDesc('books.created_at'),

            'title' => $query->orderBy('books.title'),

            default => $query
                ->orderByDesc('views_count')
                ->orderByDesc('borrow_count')
                ->orderByDesc('books.rating'),
        };

        $query->orderByDesc('books.created_at');
    }

    private function applyRecommendationPriority(Builder $query, User $user): void
    {
        $categoryWeights = collect();

        $user->loadMissing(['bookPreferences.category']);

        foreach ($user->bookPreferences as $preference) {
            if (! $preference->book_category_id) {
                continue;
            }

            $categoryId = (string) $preference->book_category_id;

            $categoryWeights[$categoryId] = max(
                (float) ($categoryWeights[$categoryId] ?? 0),
                (float) ($preference->weight ?? 0)
            );
        }

        if (filled($user->major_name)) {
            $majorKeyword = trim((string) $user->major_name);
            $majorCode = str_replace(' ', '_', strtolower($majorKeyword));

            $program = StudyProgram::query()
                ->with('categories')
                ->where('is_active', true)
                ->where(function (Builder $query) use ($majorKeyword, $majorCode) {
                    $query
                        ->where('name', 'like', '%' . $this->escapeLike($majorKeyword) . '%')
                        ->orWhere('code', 'like', '%' . $this->escapeLike($majorCode) . '%');
                })
                ->first();

            if ($program) {
                foreach ($program->categories as $category) {
                    $categoryId = (string) $category->id;

                    $categoryWeights[$categoryId] = max(
                        (float) ($categoryWeights[$categoryId] ?? 0),
                        (float) ($category->pivot->weight ?? 1)
                    );
                }
            }
        }

        $categoryIds = $categoryWeights
            ->sortDesc()
            ->keys()
            ->filter(fn ($id) => is_string($id) && $id !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($categoryIds)) {
            return;
        }

        $caseSql = 'CASE';
        $bindings = [];

        foreach ($categoryIds as $index => $categoryId) {
            $caseSql .= ' WHEN books.category_id = ? THEN ?';
            $bindings[] = $categoryId;
            $bindings[] = $index;
        }

        $caseSql .= ' ELSE ? END ASC';
        $bindings[] = count($categoryIds);

        $query->orderByRaw($caseSql, $bindings);
    }

    private function attachRackSummary(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $bookIds = $rows->pluck('book_id')->filter()->unique()->values();
        $branchIds = $rows->pluck('branch_id')->filter()->unique()->values();

        $rackRows = BookItem::query()
            ->select([
                'book_items.book_id',
                'book_items.branch_id',
                'book_items.rack_id',
            ])
            ->selectRaw('COUNT(*) as total_items_count')
            ->selectRaw("SUM(CASE WHEN book_items.status = 'available' THEN 1 ELSE 0 END) as available_items_count")
            ->selectRaw("SUM(CASE WHEN book_items.status = 'borrowed' THEN 1 ELSE 0 END) as borrowed_items_count")
            ->selectRaw("SUM(CASE WHEN book_items.status = 'lost' THEN 1 ELSE 0 END) as lost_items_count")
            ->selectRaw("SUM(CASE WHEN book_items.status = 'damaged' THEN 1 ELSE 0 END) as damaged_items_count")
            ->selectRaw("SUM(CASE WHEN book_items.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_items_count")
            ->with(['rack'])
            ->where('book_items.is_active', true)
            ->whereIn('book_items.book_id', $bookIds)
            ->whereIn('book_items.branch_id', $branchIds)
            ->whereNotNull('book_items.rack_id')
            ->groupBy([
                'book_items.book_id',
                'book_items.branch_id',
                'book_items.rack_id',
            ])
            ->get()
            ->groupBy(fn ($row) => $this->pairKey($row->book_id, $row->branch_id));

        $rows->each(function ($row) use ($rackRows) {
            $row->setRelation(
                'locationRacks',
                $rackRows->get($this->pairKey($row->book_id, $row->branch_id), collect())
            );
        });
    }

    private function attachStatusSummary(BookItem $row): void
    {
        $summary = BookItem::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->where('book_id', $row->book_id)
            ->where('branch_id', $row->branch_id)
            ->where('is_active', true)
            ->groupBy('status')
            ->pluck('total', 'status');

        $row->setAttribute('status_summary', [
            'available' => (int) ($summary['available'] ?? 0),
            'borrowed' => (int) ($summary['borrowed'] ?? 0),
            'lost' => (int) ($summary['lost'] ?? 0),
            'damaged' => (int) ($summary['damaged'] ?? 0),
            'maintenance' => (int) ($summary['maintenance'] ?? 0),
        ]);
    }

    private function recordBookView(string $bookId, ?User $user = null): void
    {
        if (! $user) {
            return;
        }

        BookView::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'book_id' => $bookId,
            'viewed_at' => now(),
        ]);
    }

    private function isRecommendationMode(array $filters): bool
    {
        return filter_var(
            $filters['is_recommendations'] ?? $filters['isRecommendations'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
    }

    private function distanceSql(string $branchAlias): string
    {
        return "
            6371 * ACOS(
                LEAST(
                    1,
                    GREATEST(
                        -1,
                        COS(RADIANS(?))
                        * COS(RADIANS({$branchAlias}.latitude))
                        * COS(RADIANS({$branchAlias}.longitude) - RADIANS(?))
                        + SIN(RADIANS(?))
                        * SIN(RADIANS({$branchAlias}.latitude))
                    )
                )
            )
        ";
    }

    private function pairKey(string $bookId, string $branchId): string
    {
        return $bookId . '|' . $branchId;
    }

    private function normalizeLimit(int $limit): int
    {
        return min(max($limit, 1), 50);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}