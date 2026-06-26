<?php

use App\Http\Controllers\Api\V1\Staff\StaffActivityLogController;
use App\Http\Controllers\Api\V1\Staff\StaffBorrowingController;
use App\Http\Controllers\Api\V1\Staff\StaffDashboardController;
use App\Http\Controllers\Api\V1\Staff\StaffNotificationController;
use App\Http\Controllers\Api\V1\Staff\StaffPaymentController;
use App\Http\Controllers\Api\V1\Staff\StaffQrScanController;
use App\Http\Controllers\Api\V1\Staff\StaffRackController;
use App\Http\Controllers\Api\V1\Staff\StaffReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Staff Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1/staff
| Auth: Sanctum
|
*/

Route::middleware('auth:sanctum')
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('borrowings')->name('borrowings.')->group(function () {
            Route::get('/', [StaffBorrowingController::class, 'index'])->name('index');
            Route::get('{borrowing}', [StaffBorrowingController::class, 'show'])->name('show');

            // Dev/testing/emergency override — confirm pickup without scanning a QR.
            // Gated by config('lumina.staff_bypass.enabled') (APP_ENABLE_STAFF_BYPASS).
            Route::post('{borrowing}/force-pickup', [StaffBorrowingController::class, 'forcePickup'])->name('force-pickup');

            // Payment management per borrowing
            Route::get('{borrowing}/payment', [StaffPaymentController::class, 'show'])->name('payment.show');
            Route::post('{borrowing}/payment/sync', [StaffPaymentController::class, 'sync'])->name('payment.sync');
        });

        Route::prefix('qr')->name('qr.')->group(function () {
            Route::post('scan-pickup', [StaffQrScanController::class, 'scanPickup'])->name('scan-pickup');

            // Preferred return flow: preview first, then confirm after physical inspection.
            Route::post('scan-return/preview', [StaffQrScanController::class, 'previewReturn'])->name('scan-return.preview');

            // Backward-compatible direct return endpoint.
            Route::post('scan-return', [StaffQrScanController::class, 'scanReturn'])->name('scan-return');

            Route::post('scan-book', [StaffQrScanController::class, 'scanBook'])->name('scan-book');
        });

        Route::prefix('returns')->name('returns.')->group(function () {
            Route::post('confirm', [StaffQrScanController::class, 'confirmReturn'])->name('confirm');
        });

        // Rack & book move management
        Route::get('racks', [StaffRackController::class, 'index'])->name('racks.index');
        Route::prefix('book-items')->name('book-items.')->group(function () {
            Route::get('{bookItem}/rack', [StaffRackController::class, 'showBookItemRack'])->name('rack');
            Route::post('{bookItem}/move', [StaffRackController::class, 'move'])->name('move');
            Route::get('{bookItem}/move-history', [StaffRackController::class, 'moveHistory'])->name('move-history');
        });

        // Reports
        Route::get('reports', [StaffReportController::class, 'index'])->name('reports.index');

        // Activity logs (branch-scoped)
        Route::get('activity-logs', [StaffActivityLogController::class, 'index'])->name('activity-logs.index');

        // Staff notifications (branch-scoped)
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [StaffNotificationController::class, 'index'])->name('index');
            Route::get('unread-count', [StaffNotificationController::class, 'unreadCount'])->name('unread-count');
            Route::put('read-all', [StaffNotificationController::class, 'markAllRead'])->name('read-all');
            Route::put('{notification}/read', [StaffNotificationController::class, 'markRead'])->name('read');
        });
    });
