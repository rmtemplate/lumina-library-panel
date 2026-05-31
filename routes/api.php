<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticationController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\QrController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Mobile API routes for Lumina Library.
| Staff and Member use Sanctum authentication.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthenticationController::class, 'register'])
            ->name('register');

        Route::post('login', [AuthenticationController::class, 'login'])
            ->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthenticationController::class, 'me'])
                ->name('me');

            Route::post('logout', [AuthenticationController::class, 'logout'])
                ->name('logout');

            Route::post('logout-all', [AuthenticationController::class, 'logoutAll'])
                ->name('logout-all');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Public Content
    |--------------------------------------------------------------------------
    */
    Route::prefix('public')->name('public.')->group(function () {
        Route::get('banners', [PublicController::class, 'getBanner'])
            ->name('banners.index');

        Route::get('categories', [PublicController::class, 'getCategories'])
            ->name('categories.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Mobile Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Books
        |--------------------------------------------------------------------------
        |
        | Books are split by branch/location.
        | Detail uses selected book + branch.
        |
        */
        Route::prefix('books')->name('books.')->group(function () {
            Route::get('/', [BookController::class, 'getBooks'])
                ->name('index');

            Route::get('{book}/branches/{branch}', [BookController::class, 'showByBranch'])
                ->name('show-by-branch');

            Route::post('{book}/wishlist', [BookController::class, 'toggleWishlist'])
                ->name('wishlist.toggle');
        });

        /*
        |--------------------------------------------------------------------------
        | Cart
        |--------------------------------------------------------------------------
        |
        | Cart can contain multiple books, but checkout must be done per branch.
        |
        */
        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])
                ->name('index');

            Route::post('items', [CartController::class, 'addItem'])
                ->name('items.store');

            Route::delete('items/{cartItem}', [CartController::class, 'removeItem'])
                ->name('items.destroy');

            Route::delete('/', [CartController::class, 'clear'])
                ->name('clear');
        });

        /*
        |--------------------------------------------------------------------------
        | Checkout
        |--------------------------------------------------------------------------
        |
        | Checkout creates borrowing, reserves stock, and generates pickup QR.
        |
        */
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('/', [CheckoutController::class, 'checkout'])
                ->name('store');
        });

        /*
        |--------------------------------------------------------------------------
        | Borrowing QR
        |--------------------------------------------------------------------------
        |
        | QR can be refreshed repeatedly while borrowing is awaiting pickup
        | and reservation has not expired.
        |
        */
        Route::prefix('borrowings')->name('borrowings.')->group(function () {
            Route::post('{borrowing}/qr/refresh', [QrController::class, 'refreshBorrowingPickup'])
                ->name('qr.refresh');
        });
    });
});