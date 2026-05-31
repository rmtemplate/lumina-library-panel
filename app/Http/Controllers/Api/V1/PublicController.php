<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookCategoryResource;
use App\Http\Resources\BookRecommendationResource;
use App\Models\User;
use App\Services\Api\PublicService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicController extends Controller
{
    public function __construct(
        private readonly PublicService $publicService
    ) {}

    public function getBanner() {
        $banners = $this->publicService->getBanners();
        return ApiResponse::success($banners, 'Active banners retrieved successfully.');
    }

    public function getCategories() {
        $categories = $this->publicService->getCategories();
        return ApiResponse::success(BookCategoryResource::collection($categories), 'Book categories retrieved successfully.');
    }

    
}
