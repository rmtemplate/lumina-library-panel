<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Checkout\CheckoutRequest;
use App\Services\Api\Checkout\CheckoutService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class CheckoutController extends ApiController
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {
    }

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $result = $this->checkoutService->checkout(
            user: $request->user(),
            payload: $request->validated()
        );

        return ApiResponse::success(
            data: $result,
            message: 'Checkout berhasil. QR peminjaman berhasil dibuat.',
            code: 'CHECKOUT_SUCCESS'
        );
    }
}
