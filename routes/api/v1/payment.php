<?php

use App\Http\Controllers\Api\V1\Payment\MidtransCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Payment Routes
|--------------------------------------------------------------------------
|
| Webhook: POST /api/v1/payments/midtrans/callback  (public, no auth)
|
| The Midtrans notification URL must point to this endpoint.
| Signature verification is performed inside the controller.
|
*/

Route::prefix('payments')->name('payments.')->group(function () {
    Route::post('midtrans/callback', [MidtransCallbackController::class, 'handle'])
        ->name('midtrans.callback');
});
