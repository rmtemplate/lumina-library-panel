<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE `notifications`
  ADD COLUMN `branch_id` char(36) NULL AFTER `user_id`,
  ADD KEY `idx_notifications_branch` (`branch_id`),
  ADD CONSTRAINT `fk_notifications_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL;
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE `notifications`
  DROP FOREIGN KEY `fk_notifications_branch`,
  DROP KEY `idx_notifications_branch`,
  DROP COLUMN `branch_id`;
SQL);
    }
};
