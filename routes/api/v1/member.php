<?php

use App\Http\Controllers\Api\V1\Member\BookController;
use App\Http\Controllers\Api\V1\Member\CartController;
use App\Http\Controllers\Api\V1\Member\CheckoutController;
use App\Http\Controllers\Api\V1\Member\MyBookController;
use App\Http\Controllers\Api\V1\Member\NotificationController;
use App\Http\Controllers\Api\V1\Member\QrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Member Routes
|--------------------------------------------------------------------------
|
| All routes require auth:sanctum.
| No /member prefix — these are the default protected mobile routes.
|
| Books:
|   GET    /api/v1/books
|   GET    /api/v1/books/{book}/branches/{branch}
|   POST   /api/v1/books/{book}/wishlist
|
| Cart:
|   GET    /api/v1/cart
|   POST   /api/v1/cart/items
|   DELETE /api/v1/cart/items/{cartItem}
|   DELETE /api/v1/cart
|
| Checkout:
|   POST   /api/v1/checkout
|
| Borrowings (QR):
|   POST   /api/v1/borrowings/{borrowing}/qr/refresh
|
| My Books:
|   GET    /api/v1/my-books
|   GET    /api/v1/my-books/{borrowing}
|   POST   /api/v1/my-books/{borrowing}/return-qr/refresh
|
*/

Route::middleware('auth:sanctum')->group(function () {

    // ── Books ─────────────────────────────────────────────────────────────
    Route::prefix('books')->name('books.')->group(function () {
        Route::get('/', [BookController::class, 'getBooks'])->name('index');
        Route::get('{book}/branches/{branch}', [BookController::class, 'showByBranch'])->name('show-by-branch');
        Route::post('{book}/wishlist', [BookController::class, 'toggleWishlist'])->name('wishlist.toggle');
    });

    // ── Cart ──────────────────────────────────────────────────────────────
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('items', [CartController::class, 'addItem'])->name('items.store');
        Route::delete('items/{cartItem}', [CartController::class, 'removeItem'])->name('items.destroy');
        Route::delete('/', [CartController::class, 'clear'])->name('clear');
    });

    // ── Checkout ──────────────────────────────────────────────────────────
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::post('/', [CheckoutController::class, 'checkout'])->name('store');
    });

    // ── Borrowings (QR Pickup Refresh) ────────────────────────────────────
    Route::prefix('borrowings')->name('borrowings.')->group(function () {
        Route::post('{borrowing}/qr/refresh', [QrController::class, 'refreshBorrowingPickup'])->name('qr.refresh');
    });

    // ── My Books ──────────────────────────────────────────────────────────
    Route::prefix('my-books')->name('my-books.')->group(function () {
        Route::get('/', [MyBookController::class, 'index'])->name('index');
        Route::get('{borrowing}', [MyBookController::class, 'show'])->name('show');
        Route::post('{borrowing}/return-qr/refresh', [MyBookController::class, 'refreshReturnQr'])->name('return-qr.refresh');
    });

    // ── Notifications ──────────────────────────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::put('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::put('{notification}/read', [NotificationController::class, 'markRead'])->name('read');
    });

});
