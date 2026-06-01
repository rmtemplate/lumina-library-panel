<?php

use App\Http\Controllers\Api\V1\Public\BranchController;
use App\Http\Controllers\Api\V1\Public\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Public Routes
|--------------------------------------------------------------------------
|
| No authentication required.
|
| GET /api/v1/public/banners
| GET /api/v1/public/categories
| GET /api/v1/public/branches               — nearby branches (+ distance & schedule)
| GET /api/v1/public/branches/{branch}      — branch detail
| GET /api/v1/public/branches/{branch}/books — books at a branch (+ search/filter)
|
*/

Route::prefix('public')->name('public.')->group(function () {
    Route::get('banners', [PublicController::class, 'getBanner'])->name('banners.index');
    Route::get('categories', [PublicController::class, 'getCategories'])->name('categories.index');

    // Library branches
    Route::prefix('branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])->name('index');
        Route::get('{branch}', [BranchController::class, 'show'])->name('show');
        Route::get('{branch}/books', [BranchController::class, 'books'])->name('books');
    });
});
