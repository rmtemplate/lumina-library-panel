<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `borrowing_reminder_logs` (
  `id` char(36) NOT NULL,
  `borrowing_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `reminder_type` varchar(30) NOT NULL COMMENT 'due_minus_3, due_minus_1, due_today, overdue_day_1, overdue_day_3, overdue_day_7',
  `channel` varchar(20) NOT NULL COMMENT 'in_app, email',
  `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reminder_sent` (`borrowing_id`, `reminder_type`, `channel`),
  KEY `idx_reminder_logs_borrowing` (`borrowing_id`),
  KEY `idx_reminder_logs_user` (`user_id`),
  CONSTRAINT `fk_reminder_logs_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reminder_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS `borrowing_reminder_logs`;');
    }
};
