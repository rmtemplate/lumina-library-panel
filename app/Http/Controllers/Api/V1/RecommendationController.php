<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookRecommendationResource;
use App\Models\User;
use App\Services\Api\RecommendationPreparationService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationPreparationService $service) {}

    /**
     * Placeholder endpoint for future mobile app integration.
     * For now it accepts user_id as a dev-time parameter until token auth is enabled.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = User::query()->findOrFail((string) $request->input('user_id'));
        $books = $this->service->recommendedBooksFor($user, $request->integer('limit', 12));

        return ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'major_name' => $user->major_name,
            ],
            'recommendations' => BookRecommendationResource::collection($books),
        ], 'Recommendation preview is ready.');
    }
}
