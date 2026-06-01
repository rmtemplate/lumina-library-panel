<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\BookRecommendationResource;
use App\Models\User;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RecommendationController
 *
 * NOTE: This controller references App\Services\Api\RecommendationPreparationService
 * which is currently not implemented. Routes for this controller are NOT registered
 * until the service is available.
 *
 * @todo Implement App\Services\Api\RecommendationPreparationService
 */
class RecommendationController extends ApiController
{
    // Service will be injected once App\Services\Api\RecommendationPreparationService is implemented.
    // public function __construct(private readonly RecommendationPreparationService $service) {}

    /**
     * Placeholder endpoint for future mobile app integration.
     */
    public function preview(Request $request): JsonResponse
    {
        return ApiResponse::error(
            message: 'Recommendation service not yet implemented.',
            code: 'NOT_IMPLEMENTED',
            statusCode: 501
        );
    }
}
