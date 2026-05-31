<?php

namespace App\Support\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        string $code = 'SUCCESS',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => self::resolveData($data),
            'meta' => self::baseMeta($meta),
            'errors' => null,
        ], $statusCode);
    }

    public static function created(
        mixed $data = null,
        string $message = 'Data berhasil dibuat.',
        string $code = 'CREATED',
        array $meta = []
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            code: $code,
            statusCode: 201,
            meta: $meta
        );
    }

    public static function updated(
        mixed $data = null,
        string $message = 'Data berhasil diperbarui.',
        string $code = 'UPDATED',
        array $meta = []
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            code: $code,
            statusCode: 200,
            meta: $meta
        );
    }

    public static function deleted(
        string $message = 'Data berhasil dihapus.',
        string $code = 'DELETED',
        array $meta = []
    ): JsonResponse {
        return self::success(
            data: null,
            message: $message,
            code: $code,
            statusCode: 200,
            meta: $meta
        );
    }

    public static function paginated(
        LengthAwarePaginator|Paginator $paginator,
        mixed $data = null,
        string $message = 'Data berhasil diambil.',
        string $code = 'DATA_FOUND',
        array $meta = []
    ): JsonResponse {
        $resolvedData = $data !== null
            ? self::resolveData($data)
            : self::resolveData($paginator->items());

        return response()->json([
            'status' => true,
            'code' => $code,
            'message' => $message,
            'data' => $resolvedData,
            'meta' => self::baseMeta(array_merge($meta, [
                'pagination' => self::paginationMeta($paginator),
            ])),
            'errors' => null,
        ]);
    }

    public static function error(
        string $message = 'Terjadi kesalahan.',
        string $code = 'ERROR',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'status' => false,
            'code' => $code,
            'message' => $message,
            'data' => null,
            'meta' => self::baseMeta($meta),
            'errors' => $errors,
        ], $statusCode);
    }

    public static function validation(
        mixed $errors,
        string $message = 'Validasi gagal.',
        string $code = 'VALIDATION_ERROR'
    ): JsonResponse {
        return self::error(
            message: $message,
            code: $code,
            statusCode: 422,
            errors: $errors
        );
    }

    public static function unauthorized(
        string $message = 'Unauthenticated.',
        string $code = 'UNAUTHENTICATED'
    ): JsonResponse {
        return self::error(
            message: $message,
            code: $code,
            statusCode: 401
        );
    }

    public static function forbidden(
        string $message = 'Kamu tidak memiliki akses.',
        string $code = 'FORBIDDEN'
    ): JsonResponse {
        return self::error(
            message: $message,
            code: $code,
            statusCode: 403
        );
    }

    public static function notFound(
        string $message = 'Data tidak ditemukan.',
        string $code = 'NOT_FOUND'
    ): JsonResponse {
        return self::error(
            message: $message,
            code: $code,
            statusCode: 404
        );
    }

    public static function serverError(
        string $message = 'Terjadi kesalahan pada server.',
        string $code = 'SERVER_ERROR',
        mixed $errors = null
    ): JsonResponse {
        return self::error(
            message: $message,
            code: $code,
            statusCode: 500,
            errors: $errors
        );
    }

    private static function baseMeta(array $meta = []): array
    {
        return array_merge([
            'request_id' => request()->headers->get('X-Request-Id') ?: (string) Str::uuid(),
            'timestamp' => now()->toISOString(),
        ], $meta);
    }

    private static function paginationMeta(LengthAwarePaginator|Paginator $paginator): array
    {
        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta['total'] = $paginator->total();
            $meta['last_page'] = $paginator->lastPage();
        }

        return $meta;
    }

    private static function resolveData(mixed $data): mixed
    {
        if ($data instanceof JsonResource) {
            return $data->resolve(request());
        }

        if ($data instanceof AnonymousResourceCollection) {
            return $data->resolve(request());
        }

        if ($data instanceof Collection) {
            return $data->values();
        }

        return $data;
    }
}