<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `borrowing_qr_codes`;
DROP TABLE IF EXISTS `fine_payments`;
DROP TABLE IF EXISTS `fines`;
DROP TABLE IF EXISTS `borrowing_renewals`;
DROP TABLE IF EXISTS `borrowing_status_histories`;
DROP TABLE IF EXISTS `borrowing_items`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `book_reservations`;
DROP TABLE IF EXISTS `book_reviews`;
DROP TABLE IF EXISTS `user_wishlists`;
DROP TABLE IF EXISTS `recommendation_events`;
DROP TABLE IF EXISTS `user_book_preferences`;
DROP TABLE IF EXISTS `study_program_book_categories`;
DROP TABLE IF EXISTS `study_programs`;
DROP TABLE IF EXISTS `book_views`;
DROP TABLE IF EXISTS `search_histories`;
DROP TABLE IF EXISTS `trending_keywords`;
DROP TABLE IF EXISTS `book_move_histories`;
DROP TABLE IF EXISTS `book_rack_assignments`;
DROP TABLE IF EXISTS `book_stocks`;
DROP TABLE IF EXISTS `book_items`;
DROP TABLE IF EXISTS `racks`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `notification_preferences`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `email_verification_tokens`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `book_categories`;
DROP TABLE IF EXISTS `library_branches`;
DROP TABLE IF EXISTS `profile_types`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `profile_types` (
  `id` char(36) NOT NULL,
  `code` varchar(30) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text NULL,
  `institution_label` varchar(100) NOT NULL DEFAULT 'Instansi',
  `major_label` varchar(100) NOT NULL DEFAULT 'Jurusan',
  `level_label` varchar(100) NOT NULL DEFAULT 'Tingkat',
  `show_institution` tinyint(1) NOT NULL DEFAULT 1,
  `show_major` tinyint(1) NOT NULL DEFAULT 1,
  `show_level` tinyint(1) NOT NULL DEFAULT 1,
  `requires_institution` tinyint(1) NOT NULL DEFAULT 0,
  `requires_major` tinyint(1) NOT NULL DEFAULT 0,
  `requires_level` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profile_types_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `library_branches` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text NULL,
  `description` text NULL,
  `phone` varchar(20) NULL,
  `email` varchar(255) NULL,
  `image_path` text NULL,
  `image_is_external` tinyint(1) NOT NULL DEFAULT 0,
  `latitude` decimal(10,7) NULL,
  `longitude` decimal(10,7) NULL,
  `open_time` time NULL,
  `close_time` time NULL,
  `schedule_description` varchar(200) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_library_branches_code` (`code`),
  KEY `idx_library_branches_active` (`is_active`),
  KEY `idx_library_branches_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` char(36) NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NULL,
  `scope` varchar(30) NOT NULL DEFAULT 'branch',
  `is_admin_role` tinyint(1) NOT NULL DEFAULT 0,
  `is_staff_role` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_code` (`code`),
  KEY `idx_roles_scope` (`scope`),
  KEY `idx_roles_admin` (`is_admin_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` char(36) NOT NULL,
  `code` varchar(120) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NULL,
  `module` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_code` (`code`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_permissions` (
  `id` char(36) NOT NULL,
  `role_id` char(36) NOT NULL,
  `permission_id` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role_permissions_permission` (`permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `branch_id` char(36) NULL,
  `fullname` varchar(200) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NULL,
  `password_hash` varchar(255) NOT NULL,
  `member_code` varchar(50) NOT NULL,
  `avatar_path` text NULL,
  `avatar_is_external` tinyint(1) NOT NULL DEFAULT 0,
  `profile_type_id` char(36) NOT NULL,
  `institution_name` varchar(200) NULL,
  `major_name` varchar(200) NULL,
  `current_level` varchar(100) NULL,
  `staff_position` varchar(100) NULL,
  `staff_library_id` char(36) NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_member_code` (`member_code`),
  KEY `idx_users_branch` (`branch_id`),
  KEY `idx_users_profile_type` (`profile_type_id`),
  KEY `idx_users_staff_library` (`staff_library_id`),
  KEY `idx_users_active` (`is_active`),
  CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_users_profile_type` FOREIGN KEY (`profile_type_id`) REFERENCES `profile_types` (`id`),
  CONSTRAINT `fk_users_staff_library` FOREIGN KEY (`staff_library_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `role_id` char(36) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` char(36) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
  KEY `idx_user_roles_role` (`role_id`),
  CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_sessions` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,

  `personal_access_token_id` bigint unsigned NULL,
  `token_hash` varchar(255) NOT NULL,

  `device_info` varchar(500) NULL,
  `ip_address` varchar(45) NULL,
  `expires_at` timestamp NULL,
  `last_activity_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_user_sessions_user` (`user_id`),
  KEY `idx_user_sessions_token` (`token_hash`),
  KEY `idx_user_sessions_personal_access_token` (`personal_access_token_id`),
  KEY `idx_user_sessions_active` (`is_active`),
  KEY `idx_user_sessions_expires` (`expires_at`),

  CONSTRAINT `fk_user_sessions_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_categories` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NULL,
  `icon_name` varchar(50) NULL,
  `image_path` text NULL,
  `image_is_external` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_categories_code` (`code`),
  UNIQUE KEY `uq_book_categories_slug` (`slug`),
  KEY `idx_book_categories_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `study_programs` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NULL,
  `keywords` json NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_study_programs_code` (`code`),
  KEY `idx_study_programs_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `study_program_book_categories` (
  `id` char(36) NOT NULL,
  `study_program_id` char(36) NOT NULL,
  `book_category_id` char(36) NOT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_program_category` (`study_program_id`, `book_category_id`),
  KEY `idx_program_category_category` (`book_category_id`),
  CONSTRAINT `fk_program_category_program` FOREIGN KEY (`study_program_id`) REFERENCES `study_programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_program_category_category` FOREIGN KEY (`book_category_id`) REFERENCES `book_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_book_preferences` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_category_id` char(36) NOT NULL,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00,
  `source` varchar(30) NOT NULL DEFAULT 'profile',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_book_preference` (`user_id`, `book_category_id`, `source`),
  KEY `idx_user_book_preferences_category` (`book_category_id`),
  CONSTRAINT `fk_user_book_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_book_preferences_category` FOREIGN KEY (`book_category_id`) REFERENCES `book_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `books` (
  `id` char(36) NOT NULL,
  `title` varchar(500) NOT NULL,
  `author` varchar(300) NOT NULL,
  `isbn` varchar(20) NULL,
  `publisher` varchar(200) NULL,
  `publish_year` int NULL,
  `description` text NULL,
  `cover_path` text NULL,
  `cover_is_external` tinyint(1) NOT NULL DEFAULT 0,
  `category_id` char(36) NOT NULL,
  `language` varchar(50) DEFAULT 'Indonesia',
  `page_count` int NULL,
  `edition` varchar(50) NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0,
  `rating_count` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_books_isbn` (`isbn`),
  KEY `idx_books_category` (`category_id`),
  KEY `idx_books_title` (`title`),
  KEY `idx_books_author` (`author`),
  FULLTEXT KEY `ft_books_search` (`title`, `author`, `description`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `recommendation_events` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NULL,
  `event_type` varchar(40) NOT NULL,
  `score_delta` decimal(6,2) NOT NULL DEFAULT 0.00,
  `metadata` json NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recommendation_events_user` (`user_id`),
  KEY `idx_recommendation_events_book` (`book_id`),
  KEY `idx_recommendation_events_type` (`event_type`),
  CONSTRAINT `fk_recommendation_events_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recommendation_events_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `racks` (
  `id` char(36) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `library_branch_id` char(36) NULL,
  `rack_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `section_name` varchar(100) NULL,
  `floor_label` varchar(50) NULL,
  `area_label` varchar(100) NULL,
  `aisle_code` varchar(50) NULL,
  `capacity` int NOT NULL DEFAULT 0,
  `is_basement` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_racks_branch_code` (`branch_id`, `rack_code`),
  KEY `idx_racks_branch` (`branch_id`),
  CONSTRAINT `fk_racks_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_items` (
  `id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `rack_id` char(36) NULL,
  `item_code` varchar(80) NOT NULL,
  `barcode` varchar(120) NULL,
  `acquisition_date` date NULL,
  `source` varchar(80) NULL,
  `condition_note` varchar(255) NULL,
  `status` varchar(30) NOT NULL DEFAULT 'available',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_items_item_code` (`item_code`),
  UNIQUE KEY `uq_book_items_barcode` (`barcode`),
  KEY `idx_book_items_book` (`book_id`),
  KEY `idx_book_items_branch` (`branch_id`),
  KEY `idx_book_items_rack` (`rack_id`),
  KEY `idx_book_items_status` (`status`),
  KEY `idx_book_items_branch_status` (`branch_id`, `status`),
  CONSTRAINT `fk_book_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_items_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_items_rack` FOREIGN KEY (`rack_id`) REFERENCES `racks` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_stocks` (
  `id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL,
  `shelf_location` varchar(100) NULL,
  `total_stock` int NOT NULL DEFAULT 0,
  `available_stock` int NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_stock_branch` (`book_id`, `library_branch_id`),
  CONSTRAINT `fk_book_stocks_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_stocks_branch` FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_rack_assignments` (
  `id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `book_item_id` char(36) NULL,
  `rack_id` char(36) NOT NULL,
  `catalog_code` varchar(50) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_book_rack_assignments_item` (`book_item_id`),
  CONSTRAINT `fk_book_rack_assignments_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_rack_assignments_item` FOREIGN KEY (`book_item_id`) REFERENCES `book_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_rack_assignments_rack` FOREIGN KEY (`rack_id`) REFERENCES `racks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_move_histories` (
  `id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `book_item_id` char(36) NULL,
  `source_rack_id` char(36) NULL,
  `destination_rack_id` char(36) NULL,
  `reason` varchar(50) NOT NULL,
  `notes` text NULL,
  `moved_by` char(36) NOT NULL,
  `moved_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_move_histories_item` (`book_item_id`),
  CONSTRAINT `fk_book_move_histories_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_move_histories_item` FOREIGN KEY (`book_item_id`) REFERENCES `book_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_book_move_histories_source` FOREIGN KEY (`source_rack_id`) REFERENCES `racks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_book_move_histories_destination` FOREIGN KEY (`destination_rack_id`) REFERENCES `racks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_book_move_histories_user` FOREIGN KEY (`moved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carts` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_carts_user` (`user_id`),
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cart_items` (
  `id` char(36) NOT NULL,
  `cart_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `book_item_id` char(36) NULL,
  `library_branch_id` char(36) NOT NULL,
  `is_selected` tinyint(1) NOT NULL DEFAULT 0,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cart_items_cart` (`cart_id`),
  CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_items_item` FOREIGN KEY (`book_item_id`) REFERENCES `book_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_cart_items_branch` FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `borrowings` (
  `id` char(36) NOT NULL,
  `borrowing_code` varchar(50) NOT NULL,
  `user_id` char(36) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `library_branch_id` char(36) NULL,
  `borrow_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `notes` text NULL,
  `approved_by` char(36) NULL,
  `approved_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_borrowings_code` (`borrowing_code`),
  KEY `idx_borrowings_user` (`user_id`),
  KEY `idx_borrowings_branch` (`branch_id`),
  KEY `idx_borrowings_status` (`status`),
  CONSTRAINT `fk_borrowings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_borrowings_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`),
  CONSTRAINT `fk_borrowings_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `borrowing_items` (
  `id` char(36) NOT NULL,
  `borrowing_id` char(36) NOT NULL,
  `book_id` char(36) NULL,
  `book_item_id` char(36) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `return_date` date NULL,
  `return_condition` varchar(50) NULL,
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_borrowing_items_borrowing` (`borrowing_id`),
  KEY `idx_borrowing_items_item` (`book_item_id`),
  KEY `idx_borrowing_items_status` (`status`),
  CONSTRAINT `fk_borrowing_items_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrowing_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_borrowing_items_item` FOREIGN KEY (`book_item_id`) REFERENCES `book_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `borrowing_status_histories` (
  `id` char(36) NOT NULL,
  `borrowing_item_id` char(36) NOT NULL,
  `from_status` varchar(20) NULL,
  `to_status` varchar(20) NOT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` char(36) NULL,
  `notes` text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_histories_item` (`borrowing_item_id`),
  CONSTRAINT `fk_borrowing_status_histories_item` FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrowing_status_histories_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `borrowing_renewals` (
  `id` char(36) NOT NULL,
  `borrowing_item_id` char(36) NOT NULL,
  `previous_due_date` date NOT NULL,
  `new_due_date` date NOT NULL,
  `renewal_count` int NOT NULL DEFAULT 1,
  `renewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `renewed_by` char(36) NOT NULL,
  `approved_by` char(36) NULL,
  `notes` text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_borrowing_renewals_item` (`borrowing_item_id`),
  KEY `idx_borrowing_renewals_at` (`renewed_at`),
  CONSTRAINT `fk_borrowing_renewals_item` FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrowing_renewals_renewed_by` FOREIGN KEY (`renewed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_borrowing_renewals_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fines` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `branch_id` char(36) NOT NULL,
  `borrowing_item_id` char(36) NOT NULL,
  `fine_amount` decimal(12,2) NOT NULL,
  `overdue_days` int NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'unpaid',
  `paid_at` timestamp NULL,
  `paid_amount` decimal(12,2) NULL,
  `waived_reason` text NULL,
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fines_user` (`user_id`),
  KEY `idx_fines_branch` (`branch_id`),
  KEY `idx_fines_status` (`status`),
  CONSTRAINT `fk_fines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_fines_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`),
  CONSTRAINT `fk_fines_borrowing_item` FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `fine_payments` (
  `id` char(36) NOT NULL,
  `fine_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(30) NOT NULL,
  `payment_reference` varchar(100) NULL,
  `payment_proof_path` text NULL,
  `verified_by` char(36) NULL,
  `verified_at` timestamp NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fine_payments_fine` (`fine_id`),
  CONSTRAINT `fk_fine_payments_fine` FOREIGN KEY (`fine_id`) REFERENCES `fines` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fine_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_fine_payments_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `borrowing_qr_codes` (
  `id` char(36) NOT NULL,
  `borrowing_id` char(36) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `qr_type` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `payload` json NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL,
  `used_by` char(36) NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_borrowing_qr_codes_code` (`qr_code`),
  KEY `idx_borrowing_qr_codes_borrowing` (`borrowing_id`),
  KEY `idx_borrowing_qr_codes_type_status` (`qr_type`, `status`),
  CONSTRAINT `fk_borrowing_qr_codes_borrowing` FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrowing_qr_codes_used_by` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `banners` (
  `id` char(36) NOT NULL,
  `branch_id` char(36) NULL,
  `title` varchar(200) NOT NULL,
  `subtitle` varchar(300) NULL,
  `image_path` text NOT NULL,
  `image_is_external` tinyint(1) NOT NULL DEFAULT 0,
  `action_url` text NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date NULL,
  `end_date` date NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  `created_by` char(36) NULL,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_banners_branch` (`branch_id`),
  CONSTRAINT `fk_banners_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `id` char(36) NOT NULL,
  `branch_id` char(36) NULL,
  `user_id` char(36) NULL,
  `activity_type` varchar(30) NOT NULL,
  `title` varchar(300) NOT NULL,
  `subtitle` text NULL,
  `reference_type` varchar(50) NULL,
  `reference_id` char(36) NULL,
  `metadata` json NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` text NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_branch` (`branch_id`),
  KEY `idx_activity_logs_user` (`user_id`),
  KEY `idx_activity_logs_type` (`activity_type`),
  CONSTRAINT `fk_activity_logs_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(300) NOT NULL,
  `body` text NULL,
  `reference_type` varchar(50) NULL,
  `reference_id` char(36) NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notification_preferences` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `borrowing_reminder` tinyint(1) NOT NULL DEFAULT 1,
  `overdue_alert` tinyint(1) NOT NULL DEFAULT 1,
  `return_confirmation` tinyint(1) NOT NULL DEFAULT 1,
  `fine_notice` tinyint(1) NOT NULL DEFAULT 1,
  `reservation_available` tinyint(1) NOT NULL DEFAULT 1,
  `system_announcement` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_preferences_user` (`user_id`),
  CONSTRAINT `fk_notification_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attachments` (
  `id` char(36) NOT NULL,
  `branch_id` char(36) NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` char(36) NOT NULL,
  `file_name` varchar(300) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` bigint NULL,
  `mime_type` varchar(100) NULL,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `uploaded_by` char(36) NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_attachments_entity` (`entity_type`, `entity_id`),
  KEY `idx_attachments_branch` (`branch_id`),
  CONSTRAINT `fk_attachments_branch` FOREIGN KEY (`branch_id`) REFERENCES `library_branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_attachments_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_tokens_user` (`user_id`),
  KEY `idx_password_reset_tokens_hash` (`token_hash`),
  CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_verification_tokens` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_verification_tokens_user` (`user_id`),
  KEY `idx_email_verification_tokens_hash` (`token_hash`),
  CONSTRAINT `fk_email_verification_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `search_histories` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `keyword` varchar(300) NOT NULL,
  `searched_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_search_histories_user` (`user_id`),
  CONSTRAINT `fk_search_histories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_views` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `viewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_views_user` (`user_id`),
  KEY `idx_book_views_book` (`book_id`),
  CONSTRAINT `fk_book_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_views_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trending_keywords` (
  `id` char(36) NOT NULL,
  `keyword` varchar(300) NOT NULL,
  `search_count` int NOT NULL DEFAULT 0,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `rank_position` int NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trending_keywords_period` (`period_start`, `period_end`),
  KEY `idx_trending_keywords_rank` (`rank_position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_reservations` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL,
  `queue_position` int NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'waiting',
  `reserved_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `available_at` timestamp NULL,
  `expires_at` timestamp NULL,
  `fulfilled_at` timestamp NULL,
  `cancelled_at` timestamp NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_book_reservations_user` (`user_id`),
  KEY `idx_book_reservations_book_branch` (`book_id`, `library_branch_id`, `status`),
  CONSTRAINT `fk_book_reservations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_reservations_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_reservations_branch` FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `book_reviews` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `rating` int NOT NULL,
  `review_text` text NULL,
  `is_verified_borrower` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_reviews_user_book` (`user_id`, `book_id`),
  CONSTRAINT `fk_book_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_book_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_wishlists` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_wishlists_user_book` (`user_id`, `book_id`),
  CONSTRAINT `fk_user_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_wishlists_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_settings` (
  `id` char(36) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `data_type` varchar(20) NOT NULL DEFAULT 'string',
  `description` text NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` char(36) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_app_settings_key` (`setting_key`),
  CONSTRAINT `fk_app_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `borrowing_qr_codes`;
DROP TABLE IF EXISTS `fine_payments`;
DROP TABLE IF EXISTS `fines`;
DROP TABLE IF EXISTS `borrowing_renewals`;
DROP TABLE IF EXISTS `borrowing_status_histories`;
DROP TABLE IF EXISTS `borrowing_items`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `book_reservations`;
DROP TABLE IF EXISTS `book_reviews`;
DROP TABLE IF EXISTS `user_wishlists`;
DROP TABLE IF EXISTS `recommendation_events`;
DROP TABLE IF EXISTS `user_book_preferences`;
DROP TABLE IF EXISTS `study_program_book_categories`;
DROP TABLE IF EXISTS `study_programs`;
DROP TABLE IF EXISTS `book_views`;
DROP TABLE IF EXISTS `search_histories`;
DROP TABLE IF EXISTS `trending_keywords`;
DROP TABLE IF EXISTS `book_move_histories`;
DROP TABLE IF EXISTS `book_rack_assignments`;
DROP TABLE IF EXISTS `book_stocks`;
DROP TABLE IF EXISTS `book_items`;
DROP TABLE IF EXISTS `racks`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `notification_preferences`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `email_verification_tokens`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `book_categories`;
DROP TABLE IF EXISTS `library_branches`;
DROP TABLE IF EXISTS `profile_types`;
SET FOREIGN_KEY_CHECKS = 1;
SQL);
    }
};
