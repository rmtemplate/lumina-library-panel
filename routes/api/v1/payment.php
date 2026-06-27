<?php

use App\Http\Controllers\Api\V1\Payment\MidtransCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Payment Webhook Routes
|--------------------------------------------------------------------------
|
| Webhook: POST /api/v1/payments/webhook  (public, no auth)
| Back-compat alias: POST /api/v1/payments/midtrans/callback (same handler)
|
| The Midtrans notification URL (MIDTRANS_NOTIFICATION_URL) should point
| at /payments/webhook going forward — the alias is kept only so any
| already-configured Midtrans dashboard notification URL keeps working.
| Signature verification is performed inside the controller.
|
| Member-facing payment endpoints (create/show/refresh/history) live in
| routes/api/v1/member.php — auth:sanctum, unlike this file.
|
*/

Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('webhook', [MidtransCallbackController::class, 'handle'])
        ->name('webhook');

    Route::post('midtrans/callback', [MidtransCallbackController::class, 'handle'])
        ->name('midtrans.callback');
});
