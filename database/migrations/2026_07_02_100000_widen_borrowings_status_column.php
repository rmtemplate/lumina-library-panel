<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The two-stage return flow introduced longer status values
 * (waiting_overdue_payment = 23, waiting_damage_payment = 22,
 * return_pending_verification = 27) that overflow the original
 * borrowings.status VARCHAR(20). Widen it so every Borrowing::* constant fits.
 *
 * Raw ALTER (not ->change()) to avoid the doctrine/dbal requirement and keep
 * the exact column semantics (NOT NULL, default 'pending').
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `borrowings` MODIFY `status` VARCHAR(40) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `borrowings` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }
};
