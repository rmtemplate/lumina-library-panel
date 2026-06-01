<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookCategoryResource;
use App\Services\Api\PublicService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicController extends ApiController
{
    public function __construct(
        private readonly PublicService $publicService
    ) {
    }

    public function getBanner(): JsonResponse
    {
        $banners = $this->publicService->getBanners();

        return ApiResponse::success(
            data: $banners,
            message: 'Active banners retrieved successfully.',
            code: 'BANNER_LIST_SUCCESS'
        );
    }

    public function getCategories(): JsonResponse
    {
        $categories = $this->publicService->getCategories();

        return ApiResponse::success(
            data: BookCategoryResource::collection($categories),
            message: 'Book categories retrieved successfully.',
            code: 'CATEGORY_LIST_SUCCESS'
        );
    }
}
