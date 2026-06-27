<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pickup no-show fines (Fine::TYPE_PICKUP_NO_SHOW) have no
     * borrowing_item — nothing was ever picked up, so there's no physical
     * item to attach the fine to. They link to the borrowing directly
     * instead, via this new nullable column. Overdue/damage/lost fines keep
     * using `borrowing_item_id` exactly as before; this column is null for
     * all of those (and vice versa — at most one of the two is set).
     */
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            if (! Schema::hasColumn('fines', 'borrowing_id')) {
                $table->uuid('borrowing_id')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('fines', 'fine_type')) {
                // overdue | damage | lost | pickup_no_show
                $table->string('fine_type', 20)->default('overdue')->after('status');
            }
        });

        // borrowing_item_id must become nullable for no-show fines.
        DB::statement('ALTER TABLE `fines` MODIFY `borrowing_item_id` char(36) NULL');

        Schema::table('fines', function (Blueprint $table) {
            $table->foreign('borrowing_id')
                ->references('id')->on('borrowings')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropForeign(['borrowing_id']);

            if (Schema::hasColumn('fines', 'borrowing_id')) {
                $table->dropColumn('borrowing_id');
            }

            if (Schema::hasColumn('fines', 'fine_type')) {
                $table->dropColumn('fine_type');
            }
        });

        DB::statement('ALTER TABLE `fines` MODIFY `borrowing_item_id` char(36) NOT NULL');
    }
};
