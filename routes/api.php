<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Entry Point
|--------------------------------------------------------------------------
|
| All API routes are versioned under /api/v1.
| Route definitions are split into domain-specific files under routes/api/v1/.
|
| Route naming convention: api.v1.<domain>.<action>
| Example: api.v1.auth.login, api.v1.books.index, api.v1.staff.dashboard
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function () {
        require __DIR__ . '/api/v1/auth.php';
        require __DIR__ . '/api/v1/public.php';
        require __DIR__ . '/api/v1/account.php';
        require __DIR__ . '/api/v1/member.php';
        require __DIR__ . '/api/v1/staff.php';
        require __DIR__ . '/api/v1/payment.php';
        require __DIR__ . '/api/v1/broadcasting.php';
    });

// Unversioned, unauthenticated, LOCAL-DEVELOPMENT-ONLY routes.
// See routes/dev.php and docs/development-bypass.md.
require __DIR__ . '/dev.php';