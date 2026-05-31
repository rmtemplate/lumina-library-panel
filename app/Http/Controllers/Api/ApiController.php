<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Responses\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        string $code = 'SUCCESS',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $code, $statusCode, $meta);
    }

    protected function created(
        mixed $data = null,
        string $message = 'Data berhasil dibuat.',
        string $code = 'CREATED',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::created($data, $message, $code, $meta);
    }

    protected function updated(
        mixed $data = null,
        string $message = 'Data berhasil diperbarui.',
        string $code = 'UPDATED',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::updated($data, $message, $code, $meta);
    }

    protected function deleted(
        string $message = 'Data berhasil dihapus.',
        string $code = 'DELETED',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::deleted($message, $code, $meta);
    }

    protected function paginated(
        LengthAwarePaginator|Paginator $paginator,
        mixed $data = null,
        string $message = 'Data berhasil diambil.',
        string $code = 'DATA_FOUND',
        array $meta = []
    ): JsonResponse {
        return ApiResponse::paginated($paginator, $data, $message, $code, $meta);
    }

    protected function error(
        string $message = 'Terjadi kesalahan.',
        string $code = 'ERROR',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return ApiResponse::error($message, $code, $statusCode, $errors, $meta);
    }
}