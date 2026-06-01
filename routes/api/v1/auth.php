<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Auth Routes
|--------------------------------------------------------------------------
|
| Public: POST /api/v1/auth/register, POST /api/v1/auth/login
| Protected (auth:sanctum): GET /api/v1/auth/me, POST /api/v1/auth/logout
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('register', [AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [AuthenticationController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthenticationController::class, 'me'])->name('me');
        Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
        Route::post('logout-all', [AuthenticationController::class, 'logoutAll'])->name('logout-all');
    });
});
