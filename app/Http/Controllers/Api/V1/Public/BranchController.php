<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookCatalogLocationResource;
use App\Services\Api\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends ApiController
{
    public function __construct(
        private readonly BranchService $branchService
    ) {
    }

    /**
     * GET /api/v1/public/branches
     *
     * List semua cabang aktif. Jika latitude & longitude dikirim:
     * - Menambahkan field distance (km + label)
     * - Menambahkan travel_estimate (jalan kaki & berkendara)
     * - Mendukung filter max_distance_km
     * - Default sort: nearest (jika ada koordinat)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'max_distance_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'sort' => ['nullable', Rule::in(['nearest', 'name'])],
        ]);

        $lat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $maxDist = isset($validated['max_distance_km']) ? (float) $validated['max_distance_km'] : null;
        $sort = $validated['sort'] ?? 'nearest';

        $branches = $this->branchService->getNearby($lat, $lng, $maxDist, $sort);

        return $this->success(
            data: $branches,
            message: 'Data cabang perpustakaan berhasil diambil.',
            code: 'BRANCHES_FOUND',
            meta: [
                'total' => $branches->count(),
                'user_location' => ($lat !== null && $lng !== null)
                    ? ['latitude' => $lat, 'longitude' => $lng]
                    : null,
            ]
        );
    }

    /**
     * GET /api/v1/public/branches/{branch}
     *
     * Detail lengkap satu cabang:
     * - Jam operasional + status buka/tutup sekarang
     * - Estimasi jarak & waktu tempuh (jika koordinat dikirim)
     * - Ringkasan stok buku
     */
    public function show(Request $request, string $branchId): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $lat = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $lng = isset($validated['longitude']) ? (float) $validated['longitude'] : null;

        $detail = $this->branchService->getDetail($branchId, $lat, $lng);

        return $this->success(
            data: $detail,
            message: 'Detail cabang perpustakaan berhasil diambil.',
            code: 'BRANCH_DETAIL_FOUND'
        );
    }

    /**
     * GET /api/v1/public/branches/{branch}/books
     *
     * Buku-buku yang tersedia di cabang tertentu.
     * Mendukung semua filter yang sama dengan GET /api/v1/books:
     *   search, category_id, status, year, sort, latitude, longitude, limit
     *
     * branch_id selalu di-force sesuai path parameter.
     */
    public function books(Request $request, string $branchId): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'uuid'],
            'category_slug' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['available', 'borrowed', 'lost', 'damaged', 'maintenance'])],
            'year' => ['nullable', 'integer', 'min:1000', 'max:' . ((int) date('Y') + 1)],
            'sort' => ['nullable', Rule::in(['trending', 'nearest', 'rating', 'newest', 'title'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $paginator = $this->branchService->getBranchBooks(
            branchId: $branchId,
            filters: $validated,
            user: $request->user()  // optional auth — untuk wishlist/review context
        );

        return $this->paginated(
            paginator: $paginator,
            data: BookCatalogLocationResource::collection($paginator),
            message: 'Buku di cabang berhasil diambil.',
            code: 'BRANCH_BOOKS_FOUND',
            meta: [
                'branch_id' => $branchId,
                'filters' => [
                    'search' => $validated['search'] ?? null,
                    'category_id' => $validated['category_id'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'sort' => $validated['sort'] ?? 'trending',
                ],
            ]
        );
    }
}
