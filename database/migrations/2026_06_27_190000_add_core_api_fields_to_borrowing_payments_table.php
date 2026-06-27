<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowing_payments', function (Blueprint $table) {
            // Discriminates what markPaid() should do next — see
            // BorrowingPaymentService::markPaid() and docs/borrowing-lifecycle.md.
            // Existing rows predate this column and were all created at
            // physical-return time, so they default to 'return_condition'.
            if (! Schema::hasColumn('borrowing_payments', 'purpose')) {
                $table->string('purpose', 30)->default('return_condition')->after('status');
            }

            // The specific Core API method the member chose (qris, bca_va,
            // gopay, ...) — distinct from `payment_type`/`payment_channel`,
            // which are populated FROM Midtrans's response after charging.
            if (! Schema::hasColumn('borrowing_payments', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('payment_channel');
            }

            // Raw Core API `actions` array (deeplink URLs for GoPay/ShopeePay,
            // QR generation links for QRIS) — not present in the old Snap
            // response shape, so this is new.
            if (! Schema::hasColumn('borrowing_payments', 'actions')) {
                $table->json('actions')->nullable()->after('qr_string');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrowing_payments', function (Blueprint $table) {
            foreach (['purpose', 'payment_method', 'actions'] as $column) {
                if (Schema::hasColumn('borrowing_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
