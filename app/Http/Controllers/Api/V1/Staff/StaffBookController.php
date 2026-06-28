<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Staff\StaffRegisterBookRequest;
use App\Services\Api\Staff\Book\StaffBookService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StaffBookController extends ApiController
{
    public function __construct(
        private readonly StaffBookService $service
    ) {
    }

    /**
     * POST /api/v1/staff/books
     *
     * Registers the catalog Book + `quantity` physical copies (BookItem),
     * each with a generated item_code/barcode and a static QR image ready
     * to print.
     */
    public function register(StaffRegisterBookRequest $request): JsonResponse
    {
        $result = $this->service->register(
            staff: $request->user(),
            payload: $request->validated(),
            cover: $request->file('cover')
        );

        $book = $result['book'];

        return ApiResponse::success(
            data: [
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'publisher' => $book->publisher,
                    'category' => $book->category ? [
                        'id' => $book->category->id,
                        'name' => $book->category->name,
                    ] : null,
                    'cover_url' => $book->cover_url,
                ],
                'items' => $result['items'],
            ],
            message: 'Buku berhasil didaftarkan.',
            code: 'STAFF_BOOK_REGISTERED'
        );
    }

    /**
     * GET /api/v1/staff/books/search?q=...
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            throw ValidationException::withMessages(['q' => ['Kata kunci pencarian wajib diisi.']]);
        }

        $items = $this->service->search($request->user(), $query);

        return ApiResponse::success(
            data: $items->map(fn ($item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'barcode' => $item->barcode,
                'book' => [
                    'id' => $item->book?->id,
                    'title' => $item->book?->title,
                    'author' => $item->book?->author,
                    'isbn' => $item->book?->isbn,
                    'cover_url' => $item->book?->cover_url,
                    'category' => $item->book?->category?->name,
                ],
                'rack' => $item->rack ? [
                    'id' => $item->rack->id,
                    'code' => $item->rack->rack_code,
                    'name' => $item->rack->name,
                ] : null,
            ])->values(),
            message: 'Pencarian buku berhasil.',
            code: 'STAFF_BOOK_SEARCH_SUCCESS'
        );
    }
}
