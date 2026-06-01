<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\MyBookDetailResource;
use App\Http\Resources\MyBookResource;
use App\Services\Api\MyBook\MyBookService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MyBookController extends ApiController
{
    public function __construct(
        private readonly MyBookService $myBookService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                Rule::in(['active', 'due_soon', 'overdue', 'returned']),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $borrowings = $this->myBookService->paginate(
            user: $request->user(),
            filters: $validated
        );

        return ApiResponse::paginated(
            paginator: $borrowings,
            data: MyBookResource::collection($borrowings),
            message: 'Daftar buku saya berhasil diambil.',
            code: 'MY_BOOK_LIST_SUCCESS',
            meta: [
                'filters' => [
                    'status' => $validated['status'] ?? null,
                ],
            ]
        );
    }

    public function show(Request $request, string $borrowing): JsonResponse
    {
        $detail = $this->myBookService->findDetail(
            user: $request->user(),
            borrowingId: $borrowing
        );

        return ApiResponse::success(
            data: new MyBookDetailResource($detail),
            message: 'Detail peminjaman berhasil diambil.',
            code: 'MY_BOOK_DETAIL_SUCCESS'
        );
    }

    public function refreshReturnQr(Request $request, string $borrowing): JsonResponse
    {
        $result = $this->myBookService->refreshReturnQr(
            user: $request->user(),
            borrowingId: $borrowing
        );

        return ApiResponse::success(
            data: $result,
            message: 'QR pengembalian berhasil diperbarui.',
            code: 'RETURN_QR_REFRESH_SUCCESS'
        );
    }
}
