<?php

namespace App\Http\Controllers\Api\Dev;

use App\Http\Controllers\Api\ApiController;
use App\Services\Api\Dev\DevBypassService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LOCAL DEVELOPMENT ONLY. Every route on this controller is gated by the
 * `dev_bypass` middleware (App\Http\Middleware\EnsureDevBypassEnabled) and is
 * intentionally unauthenticated — see that middleware for the safety
 * rationale. Do not add any route here that isn't behind that middleware.
 */
class DevBypassController extends ApiController
{
    public function __construct(
        private readonly DevBypassService $devBypassService
    ) {
    }

    public function forcePickup(Request $request, string $borrowing): JsonResponse
    {
        return $this->doPickup($request, $borrowing);
    }

    /**
     * POST /api/dev/borrowings/{id}/pickup — identical behavior to
     * force-pickup above (same DevBypassService::forcePickup() call), kept
     * as its own route/method only because that's the exact path this was
     * requested under. No logic is duplicated.
     */
    public function pickup(Request $request, string $borrowing): JsonResponse
    {
        return $this->doPickup($request, $borrowing);
    }

    private function doPickup(Request $request, string $borrowing): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->devBypassService->forcePickup(
            borrowingId: $borrowing,
            notes: $validated['notes'] ?? null
        );

        return ApiResponse::success(
            data: $result,
            message: $result['message'],
            code: 'DEV_BYPASS_PICKUP_SUCCESS'
        );
    }
}
