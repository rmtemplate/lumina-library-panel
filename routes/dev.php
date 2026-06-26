<?php

use App\Http\Controllers\Api\Dev\DevBypassController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Development-only Routes — /api/dev/*
|--------------------------------------------------------------------------
|
| UNAUTHENTICATED on purpose. Every route here MUST stay behind the
| `dev_bypass` middleware (App\Http\Middleware\EnsureDevBypassEnabled),
| which only lets requests through when APP_ENV=local, OR APP_DEBUG=true,
| OR APP_ENABLE_DEV_BYPASS=true. See docs/development-bypass.md.
|
| These routes are registered unconditionally (so `route:list` always shows
| them and nothing differs between environments at boot time) — the
| middleware is what actually blocks access outside local development.
|
*/

Route::middleware('dev_bypass')
    ->prefix('dev')
    ->name('dev.')
    ->group(function () {
        Route::prefix('borrowings')->name('borrowings.')->group(function () {
            Route::post('{borrowing}/force-pickup', [DevBypassController::class, 'forcePickup'])->name('force-pickup');
        });
    });
