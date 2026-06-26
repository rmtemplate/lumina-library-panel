<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            if (! Schema::hasColumn('borrowings', 'pickup_method')) {
                // 'qr_scan' | 'staff_bypass' — null until pickup is confirmed.
                $table->string('pickup_method', 20)->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            if (Schema::hasColumn('borrowings', 'pickup_method')) {
                $table->dropColumn('pickup_method');
            }
        });
    }
};
