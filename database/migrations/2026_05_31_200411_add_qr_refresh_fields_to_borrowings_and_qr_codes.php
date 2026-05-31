<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowings', 'reservation_expires_at')) {
                $table->timestamp('reservation_expires_at')->nullable()->after('approved_at');
            }
        });

        Schema::table('borrowing_qr_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowing_qr_codes', 'refreshed_from_id')) {
                $table->char('refreshed_from_id', 36)->nullable()->after('id');
            }

            if (! Schema::hasColumn('borrowing_qr_codes', 'refresh_count')) {
                $table->unsignedInteger('refresh_count')->default(0)->after('status');
            }

            if (! Schema::hasColumn('borrowing_qr_codes', 'last_refreshed_at')) {
                $table->timestamp('last_refreshed_at')->nullable()->after('used_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrowing_qr_codes', function (Blueprint $table) {
            if (Schema::hasColumn('borrowing_qr_codes', 'refreshed_from_id')) {
                $table->dropColumn('refreshed_from_id');
            }

            if (Schema::hasColumn('borrowing_qr_codes', 'refresh_count')) {
                $table->dropColumn('refresh_count');
            }

            if (Schema::hasColumn('borrowing_qr_codes', 'last_refreshed_at')) {
                $table->dropColumn('last_refreshed_at');
            }
        });

        Schema::table('borrowings', function (Blueprint $table) {
            if (Schema::hasColumn('borrowings', 'reservation_expires_at')) {
                $table->dropColumn('reservation_expires_at');
            }
        });
    }
};