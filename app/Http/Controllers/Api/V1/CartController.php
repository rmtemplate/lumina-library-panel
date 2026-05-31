<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Cart\AddToCartRequest;
use App\Http\Resources\CartResource;
use App\Services\Api\Cart\CartService;
use App\Support\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends ApiController
{
    public function __construct(
        private readonly CartService $cartService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request->user());

        return ApiResponse::success(
            data: new CartResource($cart),
            message: 'Cart berhasil diambil.',
            code: 'CART_DETAIL_SUCCESS'
        );
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->addItem(
            user: $request->user(),
            bookId: $request->validated('book_id'),
            branchId: $request->validated('branch_id')
        );

        return ApiResponse::created(
            data: new CartResource($cart),
            message: 'Buku berhasil ditambahkan ke cart/keranjang.',
            code: 'CART_ITEM_ADDED'
        );
    }

    public function removeItem(Request $request, string $cartItem): JsonResponse
    {
        $cart = $this->cartService->removeItem(
            user: $request->user(),
            cartItemId: $cartItem
        );

        return ApiResponse::deleted(
            message: 'Buku berhasil dihapus dari cart/keranjang.',
            code: 'CART_ITEM_REMOVED'
        );
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return ApiResponse::deleted(
            message: 'Cart/keranjang berhasil dikosongkan.',
            code: 'CART_CLEARED'
        );
    }
}