<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookCatalogLocationResource;
use App\Services\Api\Book\BookService;
use App\Support\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookController extends ApiController
{
    public function __construct(
        private readonly BookService $bookService
    ) {
    }

    public function getBooks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],

            'is_recommendations' => ['nullable', 'boolean'],
            'isRecommendations' => ['nullable', 'boolean'],

            'category_id' => ['nullable', 'uuid'],
            'category_slug' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'uuid'],

            'status' => [
                'nullable',
                Rule::in(['available', 'borrowed', 'lost', 'damaged', 'maintenance']),
            ],

            'year' => ['nullable', 'integer', 'min:1000', 'max:' . ((int) date('Y') + 1)],

            'sort' => [
                'nullable',
                Rule::in(['trending', 'nearest', 'rating', 'newest', 'title']),
            ],

            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'max_distance_km' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
        ]);

        $isRecommendations = $request->boolean('is_recommendations')
            || $request->boolean('isRecommendations');

        if ($isRecommendations && ! $request->user()) {
            return ApiResponse::unauthorized(
                message: 'Token tidak valid atau belum login.',
                code: 'UNAUTHENTICATED'
            );
        }

        $books = $this->bookService->getBooks(
            filters: $validated,
            user: $request->user()
        );

        return ApiResponse::paginated(
            paginator: $books,
            data: BookCatalogLocationResource::collection($books),
            message: $isRecommendations
                ? 'Recommended books retrieved successfully.'
                : 'Books retrieved successfully.',
            code: $isRecommendations
                ? 'BOOK_RECOMMENDATION_SUCCESS'
                : 'BOOK_LIST_SUCCESS',
            meta: [
                'mode' => $isRecommendations ? 'recommendations' : 'all',
                'split_by' => 'branch',
                'filters' => [
                    'search' => $validated['search'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                    'category_slug' => $validated['category_slug'] ?? null,
                    'branch_id' => $validated['branch_id'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'year' => $validated['year'] ?? null,
                    'sort' => $validated['sort'] ?? 'trending',
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                    'max_distance_km' => $validated['max_distance_km'] ?? null,
                ],
            ]
        );
    }

    public function showByBranch(Request $request, string $book, string $branch): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $detail = $this->bookService->getBookDetailByBranch(
            bookId: $book,
            branchId: $branch,
            user: $request->user(),
            filters: $validated
        );

        return ApiResponse::success(
            data: new BookCatalogLocationResource($detail),
            message: 'Book detail retrieved successfully.',
            code: 'BOOK_DETAIL_SUCCESS'
        );
    }

    public function toggleWishlist(Request $request, string $bookId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized(
                message: 'Token tidak valid atau belum login.',
                code: 'UNAUTHENTICATED'
            );
        }

        try {
            $isAdded = $this->bookService->addBookToWishlist($bookId, $user);
            return ApiResponse::created(
                message: $isAdded
                    ? 'Book added to wishlist successfully.'
                    : 'Book removed from wishlist successfully.',
                code: $isAdded
                    ? 'WISHLIST_ADD_SUCCESS'
                    : 'WISHLIST_REMOVE_SUCCESS'
            );
        } catch (ModelNotFoundException) {
            return ApiResponse::notFound(
                message: 'Book not found.',
                code: 'BOOK_NOT_FOUND'
            );
        }
    }
}