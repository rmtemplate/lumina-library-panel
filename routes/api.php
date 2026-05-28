<?php

use App\Http\Controllers\Api\V1\RecommendationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Future mobile API endpoints for staff and users should be registered here.
| Business logic is already placed in reusable services under app/Services.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('health', fn () => response()->json([
        'success' => true,
        'message' => 'Lumina Library API is ready.',
    ]))->name('health');

    Route::prefix('recommendations')->name('recommendations.')->group(function () {
        Route::get('preview', [RecommendationController::class, 'preview'])->name('preview');
    });
});
