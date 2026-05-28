SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `borrowing_qr_codes`;
DROP TABLE IF EXISTS `book_move_histories`;
DROP TABLE IF EXISTS `book_rack_assignments`;
DROP TABLE IF EXISTS `racks`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `notification_preferences`;
DROP TABLE IF EXISTS `user_wishlists`;
DROP TABLE IF EXISTS `book_reviews`;
DROP TABLE IF EXISTS `book_reservations`;
DROP TABLE IF EXISTS `borrowing_renewals`;
DROP TABLE IF EXISTS `fine_payments`;
DROP TABLE IF EXISTS `email_verification_tokens`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `attachments`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `trending_keywords`;
DROP TABLE IF EXISTS `book_views`;
DROP TABLE IF EXISTS `search_histories`;
DROP TABLE IF EXISTS `fines`;
DROP TABLE IF EXISTS `borrowing_status_histories`;
DROP TABLE IF EXISTS `borrowing_items`;
DROP TABLE IF EXISTS `borrowings`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `book_stocks`;
DROP TABLE IF EXISTS `library_branches`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `book_categories`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `profile_types`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `profile_types` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `code` varchar(30) UNIQUE NOT NULL COMMENT 'e.g. mahasiswa, tk_paud, bekerja',
  `label` varchar(100) NOT NULL COMMENT 'Display label: Mahasiswa, TK / PAUD',
  `description` text COMMENT 'Deskripsi tipe profil',
  `institution_label` varchar(100) NOT NULL DEFAULT 'Instansi' COMMENT 'Label dinamis untuk field institusi',
  `major_label` varchar(100) NOT NULL DEFAULT 'Jurusan' COMMENT 'Label dinamis untuk field jurusan',
  `level_label` varchar(100) NOT NULL DEFAULT 'Tingkat' COMMENT 'Label dinamis untuk field level',
  `show_institution` tinyint(1) NOT NULL DEFAULT 1,
  `show_major` tinyint(1) NOT NULL DEFAULT 1,
  `show_level` tinyint(1) NOT NULL DEFAULT 1,
  `requires_institution` tinyint(1) NOT NULL DEFAULT 0,
  `requires_major` tinyint(1) NOT NULL DEFAULT 0,
  `requires_level` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `roles` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `code` varchar(30) UNIQUE NOT NULL COMMENT 'e.g. mahasiswa, staff, librarian, admin',
  `name` varchar(100) NOT NULL COMMENT 'Display name',
  `description` text,
  `is_staff_role` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true untuk staff/librarian/admin',
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `permissions` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `code` varchar(100) UNIQUE NOT NULL COMMENT 'e.g. book.create, borrowing.approve, report.view',
  `name` varchar(150) NOT NULL,
  `description` text,
  `module` varchar(50) NOT NULL COMMENT 'Grouping: book, borrowing, report, user, system',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `role_permissions` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `role_id` char(36) NOT NULL,
  `permission_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` char(36)
);

CREATE TABLE `users` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `fullname` varchar(200) NOT NULL COMMENT 'Nama lengkap, min 3 karakter',
  `email` varchar(255) UNIQUE NOT NULL COMMENT 'Email unik, digunakan untuk login',
  `phone` varchar(20) COMMENT 'Nomor telepon opsional, min 8 digit',
  `password_hash` varchar(255) NOT NULL COMMENT 'Bcrypt/Argon2 hash',
  `member_code` varchar(50) UNIQUE NOT NULL COMMENT 'Kode anggota unik, e.g. 20210045678 atau STF-88291',
  `avatar_url` text COMMENT 'URL foto profil',
  `profile_type_id` char(36) NOT NULL,
  `institution_name` varchar(200) COMMENT 'Nama kampus/sekolah/perusahaan',
  `major_name` varchar(200) COMMENT 'Program studi/jurusan/divisi',
  `current_level` varchar(100) COMMENT 'Semester/kelas/jabatan',
  `staff_position` varchar(100) COMMENT 'Jabatan staff jika role staff',
  `staff_library_id` char(36) COMMENT 'Perpustakaan tempat staff bertugas',
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `user_roles` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `role_id` char(36) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` char(36),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp
);

CREATE TABLE `user_sessions` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL COMMENT 'Hash dari JWT/token',
  `device_info` varchar(500) COMMENT 'Info device (model, OS, dll)',
  `ip_address` varchar(45),
  `expires_at` timestamp NOT NULL,
  `last_activity_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `book_categories` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `code` varchar(50) UNIQUE NOT NULL,
  `name` varchar(150) NOT NULL COMMENT 'Nama kategori: Fiksi, Sains, Teknologi, dll',
  `description` text,
  `icon_name` varchar(50) COMMENT 'Nama icon Material untuk UI',
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `books` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `title` varchar(500) NOT NULL COMMENT 'Judul buku',
  `author` varchar(300) NOT NULL COMMENT 'Nama penulis',
  `isbn` varchar(20) UNIQUE COMMENT 'ISBN-10 atau ISBN-13',
  `publisher` varchar(200) COMMENT 'Penerbit',
  `publish_year` int COMMENT 'Tahun terbit',
  `description` text COMMENT 'Sinopsis / deskripsi buku',
  `cover_url` text COMMENT 'URL cover buku',
  `category_id` char(36) NOT NULL,
  `language` varchar(50) DEFAULT 'Indonesia',
  `page_count` int,
  `edition` varchar(50),
  `rating` decimal(3,2) NOT NULL DEFAULT 0 COMMENT 'Rating rata-rata 0.00-5.00',
  `rating_count` int NOT NULL DEFAULT 0 COMMENT 'Jumlah pemberi rating',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `library_branches` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `code` varchar(50) UNIQUE NOT NULL COMMENT 'Kode cabang: lib-central, lib-east',
  `name` varchar(200) NOT NULL COMMENT 'Nama perpustakaan',
  `address` text,
  `description` text,
  `phone` varchar(20),
  `email` varchar(255),
  `image_url` text COMMENT 'Foto gedung perpustakaan',
  `latitude` decimal(10,7) COMMENT 'Koordinat GPS',
  `longitude` decimal(10,7) COMMENT 'Koordinat GPS',
  `open_time` time COMMENT 'Jam buka, e.g. 08:00',
  `close_time` time COMMENT 'Jam tutup, e.g. 20:00',
  `schedule_description` varchar(200) COMMENT 'Teks jadwal: Senin - Jumat, 08.00 - 20.00',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `book_stocks` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `book_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL,
  `shelf_location` varchar(100) COMMENT 'Lokasi rak, e.g. Rak A-12',
  `total_stock` int NOT NULL DEFAULT 0 COMMENT 'Total eksemplar',
  `available_stock` int NOT NULL DEFAULT 0 COMMENT 'Stok tersedia (total - dipinjam)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `carts` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) UNIQUE NOT NULL COMMENT 'Satu user satu cart aktif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `cart_items` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `cart_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL COMMENT 'Perpustakaan tempat mengambil buku',
  `is_selected` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Status checkbox di UI cart',
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `borrowings` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `borrowing_code` varchar(50) UNIQUE NOT NULL COMMENT 'Kode transaksi unik, digunakan untuk QR code',
  `user_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL,
  `borrow_date` date NOT NULL COMMENT 'Tanggal pinjam',
  `due_date` date NOT NULL COMMENT 'Tanggal batas pengembalian',
  `return_date` date COMMENT 'Tanggal pengembalian aktual',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, active, returned, overdue, cancelled',
  `notes` text,
  `approved_by` char(36) COMMENT 'Staff yang menyetujui peminjaman',
  `approved_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `borrowing_items` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `borrowing_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, returned, overdue, lost, damaged',
  `return_date` date COMMENT 'Tanggal pengembalian per-item',
  `return_condition` varchar(50) COMMENT 'good, damaged, lost',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp
);

CREATE TABLE `borrowing_status_histories` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `borrowing_item_id` char(36) NOT NULL,
  `from_status` varchar(20) COMMENT 'Status sebelumnya',
  `to_status` varchar(20) NOT NULL COMMENT 'Status baru',
  `changed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `changed_by` char(36) COMMENT 'User/staff yang mengubah status',
  `notes` text
);

CREATE TABLE `fines` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `borrowing_item_id` char(36) NOT NULL,
  `fine_amount` decimal(12,2) NOT NULL COMMENT 'Jumlah denda dalam Rupiah',
  `overdue_days` int NOT NULL DEFAULT 0 COMMENT 'Jumlah hari keterlambatan',
  `status` varchar(20) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid, paid, waived',
  `paid_at` timestamp,
  `paid_amount` decimal(12,2),
  `waived_reason` text,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `search_histories` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `keyword` varchar(300) NOT NULL COMMENT 'Kata kunci pencarian',
  `searched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `book_views` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `trending_keywords` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `keyword` varchar(300) NOT NULL,
  `search_count` int NOT NULL DEFAULT 0 COMMENT 'Jumlah pencarian keyword ini',
  `period_start` date NOT NULL COMMENT 'Awal periode trending',
  `period_end` date NOT NULL COMMENT 'Akhir periode trending',
  `rank_position` int COMMENT 'Posisi ranking trending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `banners` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `title` varchar(200) NOT NULL COMMENT 'Judul banner',
  `subtitle` varchar(300),
  `image_url` text NOT NULL COMMENT 'URL gambar banner',
  `action_url` text COMMENT 'URL redirect saat diklik',
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `start_date` date COMMENT 'Tanggal mulai tayang',
  `end_date` date COMMENT 'Tanggal berakhir tayang',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `activity_logs` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) COMMENT 'User terkait, null jika system event',
  `activity_type` varchar(30) NOT NULL COMMENT 'borrow, return, overdue, register, fine, scan, login, logout',
  `title` varchar(300) NOT NULL COMMENT 'Judul aktivitas',
  `subtitle` text COMMENT 'Detail aktivitas',
  `reference_type` varchar(50) COMMENT 'Entity terkait: borrowing, book, user, fine',
  `reference_id` char(36) COMMENT 'ID entity terkait (polymorphic)',
  `metadata` json COMMENT 'Data tambahan dalam format JSON',
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `notifications` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `type` varchar(30) NOT NULL COMMENT 'borrowing_reminder, overdue_alert, return_confirmation, fine_notice, system_announcement, general',
  `title` varchar(300) NOT NULL,
  `body` text,
  `reference_type` varchar(50) COMMENT 'borrowing, fine, book',
  `reference_id` char(36) COMMENT 'ID entity terkait',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `attachments` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `entity_type` varchar(50) NOT NULL COMMENT 'user_avatar, book_cover, banner_image, library_image, document',
  `entity_id` char(36) NOT NULL COMMENT 'ID entity terkait (polymorphic)',
  `file_name` varchar(300) NOT NULL COMMENT 'Nama file asli',
  `file_path` text NOT NULL COMMENT 'Path/URL ke file di storage',
  `file_size` bigint COMMENT 'Ukuran file dalam bytes',
  `mime_type` varchar(100) COMMENT 'e.g. image/jpeg, application/pdf',
  `sort_order` int NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Apakah ini attachment utama',
  `uploaded_by` char(36),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp
);

CREATE TABLE `password_reset_tokens` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL COMMENT 'Hash token reset',
  `expires_at` timestamp NOT NULL COMMENT 'Expiry time, typically 1 hour',
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `email_verification_tokens` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `token_hash` varchar(255) NOT NULL COMMENT 'Hash token verifikasi',
  `expires_at` timestamp NOT NULL COMMENT 'Expiry time, typically 24 hours',
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `fine_payments` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `fine_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `amount` decimal(12,2) NOT NULL COMMENT 'Jumlah yang dibayar',
  `payment_method` varchar(30) NOT NULL COMMENT 'cash, bank_transfer, e_wallet',
  `payment_reference` varchar(100) COMMENT 'Nomor referensi pembayaran',
  `payment_proof_url` text COMMENT 'URL bukti pembayaran',
  `verified_by` char(36) COMMENT 'Staff yang memverifikasi',
  `verified_at` timestamp,
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, verified, rejected',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `borrowing_renewals` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `borrowing_item_id` char(36) NOT NULL,
  `previous_due_date` date NOT NULL,
  `new_due_date` date NOT NULL,
  `renewal_count` int NOT NULL DEFAULT 1 COMMENT 'Perpanjangan ke-berapa',
  `renewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `renewed_by` char(36) NOT NULL COMMENT 'User yang memperpanjang',
  `approved_by` char(36) COMMENT 'Staff yang approve, null jika auto',
  `notes` text
);

CREATE TABLE `book_reservations` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `library_branch_id` char(36) NOT NULL,
  `queue_position` int NOT NULL COMMENT 'Posisi dalam antrian',
  `status` varchar(20) NOT NULL DEFAULT 'waiting' COMMENT 'waiting, available, fulfilled, cancelled, expired',
  `reserved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `available_at` timestamp COMMENT 'Waktu buku tersedia',
  `expires_at` timestamp COMMENT 'Batas waktu ambil (24 jam setelah available)',
  `fulfilled_at` timestamp,
  `cancelled_at` timestamp,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `book_reviews` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `rating` int NOT NULL COMMENT 'Rating 1-5 stars',
  `review_text` text COMMENT 'Teks review opsional',
  `is_verified_borrower` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true jika user pernah pinjam buku ini',
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp
);

CREATE TABLE `user_wishlists` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) NOT NULL,
  `book_id` char(36) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `notification_preferences` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `user_id` char(36) UNIQUE NOT NULL,
  `borrowing_reminder` tinyint(1) NOT NULL DEFAULT 1,
  `overdue_alert` tinyint(1) NOT NULL DEFAULT 1,
  `return_confirmation` tinyint(1) NOT NULL DEFAULT 1,
  `fine_notice` tinyint(1) NOT NULL DEFAULT 1,
  `reservation_available` tinyint(1) NOT NULL DEFAULT 1,
  `system_announcement` tinyint(1) NOT NULL DEFAULT 1,
  `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `app_settings` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `setting_key` varchar(100) UNIQUE NOT NULL COMMENT 'e.g. max_borrow_days, max_renewal, fine_per_day',
  `setting_value` text NOT NULL,
  `data_type` varchar(20) NOT NULL DEFAULT 'string' COMMENT 'string, int, decimal, tinyint(1), json',
  `description` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by` char(36)
);


CREATE TABLE `racks` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `library_branch_id` char(36) NOT NULL,
  `rack_code` varchar(50) UNIQUE NOT NULL COMMENT 'Kode rak unik, e.g. 1RN002',
  `name` varchar(150) NOT NULL COMMENT 'Nama rak',
  `section_name` varchar(100) COMMENT 'Nama seksi/kategori area rak',
  `floor_label` varchar(50) COMMENT 'Label lantai, e.g. Lantai 1, Basement B1',
  `area_label` varchar(100) COMMENT 'Label area, e.g. Technology Zone',
  `aisle_code` varchar(50) COMMENT 'Kode lorong',
  `capacity` int NOT NULL DEFAULT 0 COMMENT 'Kapasitas maksimal buku pada rak',
  `is_basement` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `book_rack_assignments` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `book_id` char(36) NOT NULL,
  `rack_id` char(36) NOT NULL,
  `catalog_code` varchar(50) NOT NULL COMMENT 'Kode katalog/posisi buku di rak',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE TABLE `book_move_histories` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `book_id` char(36) NOT NULL,
  `source_rack_id` char(36) NOT NULL,
  `destination_rack_id` char(36) NOT NULL,
  `reason` varchar(50) NOT NULL COMMENT 'reorganization, new_collection, repair, capacity_adjustment, other',
  `notes` text,
  `moved_by` char(36) NOT NULL,
  `moved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE `borrowing_qr_codes` (
  `id` char(36) PRIMARY KEY COMMENT 'UUID v4',
  `borrowing_id` char(36) NOT NULL,
  `qr_code` varchar(100) UNIQUE NOT NULL COMMENT 'Token/kode QR unik yang discan oleh staff',
  `qr_type` varchar(20) NOT NULL COMMENT 'minjam, kembalikan',
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, used, expired, revoked',
  `payload` json COMMENT 'Payload QR opsional untuk client/server',
  `expires_at` timestamp NOT NULL COMMENT 'Waktu expired QR',
  `used_at` timestamp,
  `used_by` char(36) COMMENT 'Staff yang menggunakan/scan QR',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp,
  `created_by` char(36),
  `updated_by` char(36)
);

CREATE UNIQUE INDEX `uq_role_permission` ON `role_permissions` (`role_id`, `permission_id`);

CREATE INDEX `idx_users_email` ON `users` (`email`);

CREATE INDEX `idx_users_member_code` ON `users` (`member_code`);

CREATE INDEX `idx_users_profile_type` ON `users` (`profile_type_id`);

CREATE INDEX `idx_users_staff_library` ON `users` (`staff_library_id`);

CREATE UNIQUE INDEX `uq_user_role` ON `user_roles` (`user_id`, `role_id`);

CREATE INDEX `idx_user_roles_user` ON `user_roles` (`user_id`);

CREATE INDEX `idx_user_roles_role` ON `user_roles` (`role_id`);

CREATE INDEX `idx_sessions_user` ON `user_sessions` (`user_id`);

CREATE INDEX `idx_sessions_token` ON `user_sessions` (`token_hash`);

CREATE INDEX `idx_sessions_expires` ON `user_sessions` (`expires_at`);

CREATE INDEX `idx_books_title` ON `books` (`title`);

CREATE INDEX `idx_books_author` ON `books` (`author`);

CREATE INDEX `idx_books_isbn` ON `books` (`isbn`);

CREATE INDEX `idx_books_category` ON `books` (`category_id`);

CREATE INDEX `idx_books_year` ON `books` (`publish_year`);

CREATE INDEX `idx_books_rating` ON `books` (`rating`);

CREATE INDEX `idx_library_branches_code` ON `library_branches` (`code`);

CREATE INDEX `idx_library_branches_name` ON `library_branches` (`name`);

CREATE UNIQUE INDEX `uq_book_stock_branch` ON `book_stocks` (`book_id`, `library_branch_id`);

CREATE INDEX `idx_book_stocks_book` ON `book_stocks` (`book_id`);

CREATE INDEX `idx_book_stocks_branch` ON `book_stocks` (`library_branch_id`);

CREATE INDEX `idx_book_stocks_available` ON `book_stocks` (`available_stock`);

CREATE INDEX `idx_carts_user` ON `carts` (`user_id`);

CREATE INDEX `idx_cart_items_cart` ON `cart_items` (`cart_id`);

CREATE UNIQUE INDEX `uq_cart_item` ON `cart_items` (`cart_id`, `book_id`, `library_branch_id`);

CREATE INDEX `idx_borrowings_code` ON `borrowings` (`borrowing_code`);

CREATE INDEX `idx_borrowings_user` ON `borrowings` (`user_id`);

CREATE INDEX `idx_borrowings_branch` ON `borrowings` (`library_branch_id`);

CREATE INDEX `idx_borrowings_status` ON `borrowings` (`status`);

CREATE INDEX `idx_borrowings_borrow_date` ON `borrowings` (`borrow_date`);

CREATE INDEX `idx_borrowings_due_date` ON `borrowings` (`due_date`);

CREATE INDEX `idx_borrowings_user_status` ON `borrowings` (`user_id`, `status`);

CREATE INDEX `idx_borrowing_items_borrowing` ON `borrowing_items` (`borrowing_id`);

CREATE INDEX `idx_borrowing_items_book` ON `borrowing_items` (`book_id`);

CREATE INDEX `idx_borrowing_items_status` ON `borrowing_items` (`status`);

CREATE UNIQUE INDEX `uq_borrowing_item` ON `borrowing_items` (`borrowing_id`, `book_id`);

CREATE INDEX `idx_status_history_item` ON `borrowing_status_histories` (`borrowing_item_id`);

CREATE INDEX `idx_status_history_changed_at` ON `borrowing_status_histories` (`changed_at`);

CREATE INDEX `idx_fines_user` ON `fines` (`user_id`);

CREATE INDEX `idx_fines_borrowing_item` ON `fines` (`borrowing_item_id`);

CREATE INDEX `idx_fines_status` ON `fines` (`status`);

CREATE INDEX `idx_fines_user_status` ON `fines` (`user_id`, `status`);

CREATE INDEX `idx_search_histories_user` ON `search_histories` (`user_id`);

CREATE INDEX `idx_search_histories_user_time` ON `search_histories` (`user_id`, `searched_at`);

CREATE INDEX `idx_search_histories_keyword` ON `search_histories` (`keyword`);

CREATE INDEX `idx_book_views_user` ON `book_views` (`user_id`);

CREATE INDEX `idx_book_views_book` ON `book_views` (`book_id`);

CREATE INDEX `idx_book_views_user_time` ON `book_views` (`user_id`, `viewed_at`);

CREATE INDEX `idx_trending_keyword` ON `trending_keywords` (`keyword`);

CREATE INDEX `idx_trending_period` ON `trending_keywords` (`period_start`, `period_end`);

CREATE INDEX `idx_trending_rank` ON `trending_keywords` (`rank_position`);

CREATE INDEX `idx_activity_logs_user` ON `activity_logs` (`user_id`);

CREATE INDEX `idx_activity_logs_type` ON `activity_logs` (`activity_type`);

CREATE INDEX `idx_activity_logs_created` ON `activity_logs` (`created_at`);

CREATE INDEX `idx_activity_logs_reference` ON `activity_logs` (`reference_type`, `reference_id`);

CREATE INDEX `idx_activity_logs_user_type` ON `activity_logs` (`user_id`, `activity_type`);

CREATE INDEX `idx_notifications_user` ON `notifications` (`user_id`);

CREATE INDEX `idx_notifications_user_read` ON `notifications` (`user_id`, `is_read`);

CREATE INDEX `idx_notifications_type` ON `notifications` (`type`);

CREATE INDEX `idx_notifications_created` ON `notifications` (`created_at`);

CREATE INDEX `idx_attachments_entity` ON `attachments` (`entity_type`, `entity_id`);

CREATE INDEX `idx_attachments_type` ON `attachments` (`entity_type`);

CREATE INDEX `idx_attachments_uploader` ON `attachments` (`uploaded_by`);

CREATE INDEX `idx_pwd_reset_token` ON `password_reset_tokens` (`token_hash`);

CREATE INDEX `idx_pwd_reset_user` ON `password_reset_tokens` (`user_id`);

CREATE INDEX `idx_pwd_reset_user_used` ON `password_reset_tokens` (`user_id`, `is_used`);

CREATE INDEX `idx_email_verify_token` ON `email_verification_tokens` (`token_hash`);

CREATE INDEX `idx_email_verify_user` ON `email_verification_tokens` (`user_id`);

CREATE INDEX `idx_fine_payments_fine` ON `fine_payments` (`fine_id`);

CREATE INDEX `idx_fine_payments_user` ON `fine_payments` (`user_id`);

CREATE INDEX `idx_fine_payments_status` ON `fine_payments` (`status`);

CREATE INDEX `idx_renewals_item` ON `borrowing_renewals` (`borrowing_item_id`);

CREATE INDEX `idx_renewals_at` ON `borrowing_renewals` (`renewed_at`);

CREATE INDEX `idx_reservations_user` ON `book_reservations` (`user_id`);

CREATE INDEX `idx_reservations_book` ON `book_reservations` (`book_id`);

CREATE INDEX `idx_reservations_book_status` ON `book_reservations` (`book_id`, `library_branch_id`, `status`);

CREATE INDEX `idx_reservations_queue` ON `book_reservations` (`queue_position`);

CREATE UNIQUE INDEX `uq_user_book_review` ON `book_reviews` (`user_id`, `book_id`);

CREATE INDEX `idx_reviews_book` ON `book_reviews` (`book_id`);

CREATE INDEX `idx_reviews_user` ON `book_reviews` (`user_id`);

CREATE INDEX `idx_reviews_rating` ON `book_reviews` (`rating`);

CREATE UNIQUE INDEX `uq_user_wishlist` ON `user_wishlists` (`user_id`, `book_id`);

CREATE INDEX `idx_wishlists_user` ON `user_wishlists` (`user_id`);


CREATE INDEX `idx_racks_branch` ON `racks` (`library_branch_id`);
CREATE INDEX `idx_racks_code` ON `racks` (`rack_code`);
CREATE INDEX `idx_racks_active` ON `racks` (`is_active`);

CREATE UNIQUE INDEX `uq_book_rack_assignment` ON `book_rack_assignments` (`book_id`, `rack_id`, `catalog_code`);
CREATE INDEX `idx_book_rack_assignments_book` ON `book_rack_assignments` (`book_id`);
CREATE INDEX `idx_book_rack_assignments_rack` ON `book_rack_assignments` (`rack_id`);
CREATE INDEX `idx_book_rack_assignments_active` ON `book_rack_assignments` (`is_active`);

CREATE INDEX `idx_book_move_histories_book` ON `book_move_histories` (`book_id`);
CREATE INDEX `idx_book_move_histories_source` ON `book_move_histories` (`source_rack_id`);
CREATE INDEX `idx_book_move_histories_destination` ON `book_move_histories` (`destination_rack_id`);
CREATE INDEX `idx_book_move_histories_moved_by` ON `book_move_histories` (`moved_by`);
CREATE INDEX `idx_book_move_histories_moved_at` ON `book_move_histories` (`moved_at`);

CREATE INDEX `idx_borrowing_qr_codes_borrowing` ON `borrowing_qr_codes` (`borrowing_id`);
CREATE INDEX `idx_borrowing_qr_codes_code` ON `borrowing_qr_codes` (`qr_code`);
CREATE INDEX `idx_borrowing_qr_codes_type` ON `borrowing_qr_codes` (`qr_type`);
CREATE INDEX `idx_borrowing_qr_codes_status` ON `borrowing_qr_codes` (`status`);
CREATE INDEX `idx_borrowing_qr_codes_expires` ON `borrowing_qr_codes` (`expires_at`);
CREATE INDEX `idx_borrowing_qr_codes_type_status` ON `borrowing_qr_codes` (`qr_type`, `status`);

ALTER TABLE `profile_types` COMMENT = 'Master tipe profil pengguna. Menentukan field mana yang ditampilkan dan wajib diisi saat registrasi.';

ALTER TABLE `roles` COMMENT = 'Master role pengguna. Mendukung multi-role assignment.';

ALTER TABLE `permissions` COMMENT = 'Master permission untuk RBAC. Digunakan bersama role_permissions.';

ALTER TABLE `role_permissions` COMMENT = 'Junction table antara roles dan permissions.';

ALTER TABLE `users` COMMENT = 'Tabel utama pengguna. Mencakup mahasiswa dan staff. Field staff_* hanya diisi jika user adalah staff.';

ALTER TABLE `user_roles` COMMENT = 'Junction table user-role. Mendukung multi-role per user.';

ALTER TABLE `user_sessions` COMMENT = 'Session management untuk auth. Mendukung multi-device.';

ALTER TABLE `book_categories` COMMENT = 'Master kategori buku. Digunakan untuk filter catalog.';

ALTER TABLE `books` COMMENT = 'Master data buku. Stok dikelola terpisah per perpustakaan di book_stocks.';

ALTER TABLE `library_branches` COMMENT = 'Master cabang perpustakaan. Bisa lebih dari satu kampus/gedung.';

ALTER TABLE `book_stocks` COMMENT = 'Stok buku per cabang perpustakaan. Memisahkan data stok dari master buku untuk mendukung multi-branch.';

ALTER TABLE `carts` COMMENT = 'Cart peminjaman. Satu user hanya punya satu cart aktif.';

ALTER TABLE `cart_items` COMMENT = 'Item dalam cart. Buku dikelompokkan per perpustakaan (CartLibraryGroupModel).';

ALTER TABLE `borrowings` COMMENT = 'Transaksi peminjaman (header). Satu borrowing bisa mencakup beberapa buku. QR code di-generate dari borrowing_code.';

ALTER TABLE `borrowing_items` COMMENT = 'Detail buku per transaksi peminjaman. Setiap buku bisa memiliki status pengembalian sendiri.';

ALTER TABLE `borrowing_status_histories` COMMENT = 'Riwayat perubahan status setiap item peminjaman. Audit trail lengkap.';

ALTER TABLE `fines` COMMENT = 'Denda keterlambatan pengembalian buku. Total Denda ditampilkan di profil mahasiswa.';

ALTER TABLE `search_histories` COMMENT = 'Riwayat pencarian user. Ditampilkan di Search Page ala TikTok.';

ALTER TABLE `book_views` COMMENT = 'Buku terakhir dilihat. Ditampilkan di Search Page sebagai recently viewed.';

ALTER TABLE `trending_keywords` COMMENT = 'Keyword trending untuk Search Page. Dihitung berkala dari search_histories.';

ALTER TABLE `banners` COMMENT = 'Banner promosi carousel di dashboard mahasiswa.';

ALTER TABLE `activity_logs` COMMENT = 'Log aktivitas sistem. Recent activity di dashboard. Polymorphic reference ke berbagai entity.';

ALTER TABLE `notifications` COMMENT = 'Notifikasi ke user. Reminder pengembalian, alert keterlambatan, konfirmasi transaksi.';

ALTER TABLE `attachments` COMMENT = 'Tabel attachment reusable polymorphic. Mendukung upload file untuk user, buku, banner, perpustakaan.';

ALTER TABLE `password_reset_tokens` COMMENT = 'Token untuk forgot password flow. Expires dalam 1 jam. Single-use.';

ALTER TABLE `email_verification_tokens` COMMENT = 'Token verifikasi email setelah registrasi. Expires dalam 24 jam.';

ALTER TABLE `fine_payments` COMMENT = 'Pembayaran denda. Staff memverifikasi sebelum denda dianggap lunas.';

ALTER TABLE `borrowing_renewals` COMMENT = 'Log perpanjangan peminjaman. Max renewal bisa diatur di app_settings.';

ALTER TABLE `book_reservations` COMMENT = 'Antrian reservasi buku saat stok habis. Hold 24 jam saat buku tersedia.';

ALTER TABLE `book_reviews` COMMENT = 'Rating & review buku oleh user. Satu user satu review per buku. Update average di books.rating.';

ALTER TABLE `user_wishlists` COMMENT = 'Buku favorit/bookmark per user.';

ALTER TABLE `notification_preferences` COMMENT = 'Preferensi notifikasi per user. Granular per tipe notifikasi.';

ALTER TABLE `app_settings` COMMENT = 'Konfigurasi global sistem. e.g. max_borrow_days=14, max_renewal=2, fine_per_day=1000';


ALTER TABLE `racks` COMMENT = 'Master rak perpustakaan per cabang. Digunakan untuk manajemen lokasi buku.';
ALTER TABLE `book_rack_assignments` COMMENT = 'Mapping buku ke rak beserta kode katalog/posisi.';
ALTER TABLE `book_move_histories` COMMENT = 'Riwayat pemindahan buku antar rak oleh staff.';
ALTER TABLE `borrowing_qr_codes` COMMENT = 'QR transaksi peminjaman/pengembalian. Mendukung tipe minjam/kembalikan dan masa expired.';

ALTER TABLE `role_permissions` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `role_permissions` ADD FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`profile_type_id`) REFERENCES `profile_types` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`staff_library_id`) REFERENCES `library_branches` (`id`);

ALTER TABLE `user_roles` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `user_roles` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `user_roles` ADD FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`);

ALTER TABLE `user_sessions` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `books` ADD FOREIGN KEY (`category_id`) REFERENCES `book_categories` (`id`);

ALTER TABLE `book_stocks` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `book_stocks` ADD FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`);

ALTER TABLE `carts` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `cart_items` ADD FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`);

ALTER TABLE `borrowings` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `borrowings` ADD FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`);

ALTER TABLE `borrowings` ADD FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowing_items` ADD FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`);

ALTER TABLE `borrowing_items` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `borrowing_status_histories` ADD FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`);

ALTER TABLE `borrowing_status_histories` ADD FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

ALTER TABLE `fines` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `fines` ADD FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`);

ALTER TABLE `search_histories` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `book_views` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `book_views` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `activity_logs` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `notifications` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `attachments` ADD FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`);

ALTER TABLE `password_reset_tokens` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `email_verification_tokens` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `fine_payments` ADD FOREIGN KEY (`fine_id`) REFERENCES `fines` (`id`);

ALTER TABLE `fine_payments` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `fine_payments` ADD FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowing_renewals` ADD FOREIGN KEY (`borrowing_item_id`) REFERENCES `borrowing_items` (`id`);

ALTER TABLE `borrowing_renewals` ADD FOREIGN KEY (`renewed_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowing_renewals` ADD FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_reservations` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `book_reservations` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `book_reservations` ADD FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`);

ALTER TABLE `book_reviews` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `book_reviews` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `user_wishlists` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `user_wishlists` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

ALTER TABLE `notification_preferences` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `app_settings` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);


ALTER TABLE `racks` ADD FOREIGN KEY (`library_branch_id`) REFERENCES `library_branches` (`id`);
ALTER TABLE `racks` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
ALTER TABLE `racks` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_rack_assignments` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
ALTER TABLE `book_rack_assignments` ADD FOREIGN KEY (`rack_id`) REFERENCES `racks` (`id`);
ALTER TABLE `book_rack_assignments` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
ALTER TABLE `book_rack_assignments` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_move_histories` ADD FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);
ALTER TABLE `book_move_histories` ADD FOREIGN KEY (`source_rack_id`) REFERENCES `racks` (`id`);
ALTER TABLE `book_move_histories` ADD FOREIGN KEY (`destination_rack_id`) REFERENCES `racks` (`id`);
ALTER TABLE `book_move_histories` ADD FOREIGN KEY (`moved_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowing_qr_codes` ADD FOREIGN KEY (`borrowing_id`) REFERENCES `borrowings` (`id`);
ALTER TABLE `borrowing_qr_codes` ADD FOREIGN KEY (`used_by`) REFERENCES `users` (`id`);
ALTER TABLE `borrowing_qr_codes` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
ALTER TABLE `borrowing_qr_codes` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `users` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `books` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `books` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowings` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `borrowings` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `fines` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `fines` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `banners` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `banners` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_categories` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_categories` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `library_branches` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `library_branches` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_stocks` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `book_stocks` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `profile_types` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `profile_types` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `roles` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `roles` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

ALTER TABLE `permissions` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `permissions` ADD FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);
