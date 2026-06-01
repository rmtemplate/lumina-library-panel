<?php

use App\Http\Controllers\Api\V1\Account\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Account Routes
|--------------------------------------------------------------------------
|
| All routes require auth:sanctum.
| Prefix: /api/v1/account
|
| GET    /api/v1/account/profile
| PUT    /api/v1/account/profile
| POST   /api/v1/account/avatar
| DELETE /api/v1/account/avatar
| PUT    /api/v1/account/password
|
*/

Route::middleware('auth:sanctum')
    ->prefix('account')
    ->name('account.')
    ->group(function () {
        Route::get('profile', [AccountController::class, 'profile'])->name('profile');
        Route::put('profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::post('avatar', [AccountController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('avatar', [AccountController::class, 'deleteAvatar'])->name('avatar.delete');
        Route::put('password', [AccountController::class, 'updatePassword'])->name('password.update');
    });
