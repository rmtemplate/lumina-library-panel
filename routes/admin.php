<?php

use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BookCategoryController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookItemController;
use App\Http\Controllers\Admin\BookItemQrController;
use App\Http\Controllers\Admin\BorrowingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FineController;
use App\Http\Controllers\Admin\LibraryBranchController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileTypeController;
use App\Http\Controllers\Admin\RackController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/roles', [UserController::class, 'syncRoles'])->name('users.roles.sync');

        Route::resource('books', BookController::class);
        Route::patch('books/{book}/toggle-status', [BookController::class, 'toggleStatus'])->name('books.toggle-status');
        // QR labels for physical copies — bulk routes first so the literal
        // `qr` segment is never captured as a {bookItem} binding.
        Route::post('book-items/qr/bulk/print', [BookItemQrController::class, 'bulkPrint'])->name('book-items.qr.bulk-print');
        Route::post('book-items/qr/bulk/zip', [BookItemQrController::class, 'bulkZip'])->name('book-items.qr.bulk-zip');
        Route::get('book-items/{bookItem}/qr', [BookItemQrController::class, 'preview'])->name('book-items.qr.preview');
        Route::get('book-items/{bookItem}/qr/png', [BookItemQrController::class, 'png'])->name('book-items.qr.png');
        Route::get('book-items/{bookItem}/qr/svg', [BookItemQrController::class, 'svg'])->name('book-items.qr.svg');
        Route::get('book-items/{bookItem}/qr/print', [BookItemQrController::class, 'print'])->name('book-items.qr.print');
        Route::resource('book-items', BookItemController::class)->except(['show']);
        Route::resource('branches', LibraryBranchController::class);
        Route::resource('racks', RackController::class)->except(['show']);
        Route::resource('banners', BannerController::class)->only(['index', 'edit', 'update', 'destroy']);

        Route::middleware('super_admin')->group(function () {
            Route::get('banners/create', [BannerController::class, 'create'])->name('banners.create');
            Route::post('banners', [BannerController::class, 'store'])->name('banners.store');
        });

        Route::middleware('super_admin')->group(function () {
            Route::resource('categories', BookCategoryController::class)->except(['show']);
            Route::resource('profile-types', ProfileTypeController::class)->parameters(['profile-types' => 'profileType'])->except(['show']);
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::resource('permissions', PermissionController::class)->except(['show']);
        });

        Route::get('borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
        Route::get('borrowings/{borrowing}', [BorrowingController::class, 'show'])->name('borrowings.show');
        Route::get('returnings/{borrowing}', [BorrowingController::class, 'show'])->name('returnings.show');
        Route::patch('borrowings/{borrowing}/approve', [BorrowingController::class, 'approve'])->name('borrowings.approve');
        Route::patch('borrowings/{borrowing}/cancel', [BorrowingController::class, 'cancel'])->name('borrowings.cancel');

        Route::get('fines', [FineController::class, 'index'])->name('fines.index');
        Route::get('fines/{fine}', [FineController::class, 'show'])->name('fines.show');
        Route::patch('fines/{fine}/waive', [FineController::class, 'waive'])->name('fines.waive');

        Route::middleware('super_admin')->group(function () {
            Route::get('settings', [AppSettingController::class, 'index'])->name('settings.index');
            Route::put('settings', [AppSettingController::class, 'update'])->name('settings.update');
        });
    });
