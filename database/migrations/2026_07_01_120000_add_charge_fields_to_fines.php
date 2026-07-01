<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual/custom staff charges (Cover Damage, Water Damage, Cleaning Fee, …)
 * are stored as Fine rows with fine_type='custom', borrowing_id set and
 * borrowing_item_id null — same table, same payment/settlement path. They
 * just need a human-facing title + description alongside the amount, which
 * the overdue/damage fine types never required.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->string('title')->nullable()->after('fine_type');
            $table->text('description')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};
