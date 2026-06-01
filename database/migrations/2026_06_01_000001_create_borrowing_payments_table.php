<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE `borrowing_payments` (
  `id` char(36) NOT NULL,
  `borrowing_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `midtrans_order_id` varchar(100) NOT NULL,
  `snap_token` text NULL,
  `payment_url` text NULL,
  `midtrans_transaction_id` varchar(100) NULL,
  `payment_type` varchar(50) NULL,
  `payment_channel` varchar(50) NULL,
  `va_number` varchar(100) NULL,
  `qr_string` text NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL,
  `expired_at` timestamp NULL,
  `midtrans_raw` json NULL,
  `notes` text NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_borrowing_payments_order_id` (`midtrans_order_id`),
  KEY `idx_borrowing_payments_borrowing` (`borrowing_id`),
  KEY `idx_borrowing_payments_status` (`status`),
  KEY `idx_borrowing_payments_user` (`user_id`),
  CONSTRAINT `fk_borrowing_payments_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrowing_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS `borrowing_payments`;');
    }
};
