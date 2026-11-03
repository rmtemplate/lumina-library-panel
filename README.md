<p align="center">
  <img src="public/lumina-logo.webp" alt="Lumina Library Admin Icon" width="104" height="104">
</p>

<h1 align="center">Lumina Library Admin</h1>

<p align="center">Case study: <a href="https://rmtemplate.com/explore/lumina-library-admin">rmtemplate.com/explore/lumina-library-admin</a></p>

<p align="center">
  Modern multi-branch library management system built with Laravel, Tailwind CSS, service-layer architecture, and future API/mobile readiness.
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-12%2F13-FF2D20?style=flat-square&logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="Tailwind CSS" src="https://img.shields.io/badge/Tailwind_CSS-Admin_UI-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-Ready-4479A1?style=flat-square&logo=mysql&logoColor=white">
  <img alt="Academic Project" src="https://img.shields.io/badge/Academic_Project-Tugas_Kuliah-006C49?style=flat-square">
</p>
<p align="center">
  <strong>Author:</strong> Reza Maulana &nbsp;•&nbsp;
  <strong>License:</strong> RM Template &nbsp;•&nbsp;
  <a href="https://rmtemplate.com" target="_blank">rmtemplate.com</a>
</p>


---

## Overview

**Lumina Library Admin** adalah backend dan web admin untuk sistem manajemen perpustakaan multi-cabang. Project ini disiapkan untuk kebutuhan real-world library management, dengan pemisahan role, branch scope, service layer, dan pondasi REST API untuk mobile app Staff/User di masa depan.


## Academic Purpose

Project ini dibuat sebagai **tugas kuliah** oleh **Reza Maulana**. Walaupun disusun dengan pendekatan clean architecture dan production-style structure, tujuan utama project ini adalah sebagai implementasi pembelajaran pengembangan aplikasi web, backend, database, UI admin, multi-branch authorization, dan pondasi future API/mobile integration.

---

## Key Features

### Admin Web

- Admin login khusus untuk **Super Admin** dan **Admin Branch**.
- Staff dan Member tidak dapat login ke admin panel.
- Sidebar modern, responsive, grouped menu, dan permission-aware.
- Dashboard dengan statistik, summary, quick actions, recent activity, dan chart area.
- Table modern dengan pagination, search, filter, sort, page size, dan action menu.
- SweetAlert2 toast dan confirmation dialog.
- UI memakai Tailwind CSS dan design system Modern Academic.

### Multi-Branch Library

- Setiap branch/perpustakaan memiliki data sendiri.
- Super Admin dapat melihat dan mengelola semua branch.
- Admin Branch hanya dapat mengakses data branch miliknya.
- Staff/Petugas disiapkan untuk operasional mobile/API, bukan admin dashboard.

### Book Management

- `books` menyimpan master judul buku.
- `book_items` menyimpan copy fisik/satuan buku per branch.
- Setiap copy buku memiliki status:
  - `available`
  - `borrowed`
  - `lost`
  - `damaged`
  - `maintenance`

### Branch Location Map

- Branch form memakai Leaflet/OpenStreetMap.
- Search lokasi/alamat.
- Marker/pin draggable.
- Klik map untuk pindah titik.
- Latitude dan longitude auto-fill.
- Detail branch menampilkan preview lokasi dengan marker.

### Category Management

- Categories hanya dapat dikelola oleh Super Admin.
- Admin Branch hanya dapat memakai category pada form buku.
- Category memiliki:
  - name
  - slug
  - description
  - image/icon upload
- Upload image memakai file, bukan URL manual.
- Seeder tetap dapat memakai dummy external image.

### Profile Type

Master profile type disiapkan untuk user/member, statistik, dan future recommendation system.

Seeder default:

- TK
- SD
- SMP
- SMA/SMK
- Mahasiswa/i
- Pekerja
- Umum

### Recommendation Preparation

Project sudah menyiapkan pondasi rekomendasi buku berbasis user profile/study program/category:

- `study_programs`
- `study_program_book_categories`
- `user_book_preferences`
- `recommendation_events`
- `RecommendationPreparationService`
- placeholder endpoint recommendation preview

---

## Tech Stack

- Laravel 12/13 style structure
- PHP 8.3+
- MySQL/MariaDB
- Blade
- Tailwind CSS
- Vite
- SweetAlert2
- Chart.js-ready dashboard area
- Leaflet + OpenStreetMap
- UUID primary keys

---

## Project Structure

```txt
app/
├── Enums/                  # Status, role code, QR type/status enum
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Web admin controllers
│   │   ├── Api/V1/         # Future mobile/API controllers
│   │   └── Auth/           # Admin authentication
│   ├── Middleware/         # Admin, Super Admin, Staff/role guard
│   └── Requests/Admin/     # Form request validation
├── Models/                 # Eloquent models and relationships
├── Services/               # Business logic / service layer
├── Support/                # Shared response/helper utilities
└── Traits/                 # Shared model traits

database/
├── migrations/             # Clean schema for fresh migration
├── seeders/                # Demo/reference data
└── factories/              # Model factories

resources/
├── css/app.css             # Tailwind entry
├── js/app.js               # Admin UI behavior, map, toast, dropdown
└── views/
    ├── admin/              # Admin pages
    ├── auth/               # Admin login
    ├── components/admin/   # Reusable Blade UI components
    └── layouts/            # App/admin layouts

routes/
├── admin.php               # Protected admin routes
├── api.php                 # Future API placeholder
├── auth.php                # Login/logout auth routes
├── staff.php               # Reserved for future staff flow
├── user.php                # Reserved for future member flow
└── web.php                 # Main route loader
```

---

## Installation

Clone or extract this project into your Laravel workspace.

```bash
git clone <repository-url> lumina-library-panel
cd lumina-library-panel
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lumina-library
DB_USERNAME=root
DB_PASSWORD=
```

Run storage link:

```bash
php artisan storage:link
```

Run migration and seeder:

```bash
php artisan migrate:fresh --seed
```

Build assets:

```bash
npm run build
```

For development:

```bash
npm run dev
```

Run local server:

```bash
php artisan serve
```

---

## Default Login

### Super Admin

```txt
Email: super@lumina.ac.id
Password: password
```

### Admin Branch Central

```txt
Email: admin.central@lumina.ac.id
Password: password
```

### Admin Branch East

```txt
Email: admin.east@lumina.ac.id
Password: password
```

Staff and Member accounts are not allowed to access the admin dashboard.

---

## Role Access Matrix

| Role | Admin Login | Global Data | Branch Data | Categories | Roles/Permissions | Books | Transactions |
|---|---:|---:|---:|---:|---:|---:|---:|
| Super Admin | Yes | Yes | All Branches | Manage | Manage | Manage All | Manage All |
| Admin Branch | Yes | No | Own Branch Only | Use Only | No | Own Branch | Own Branch |
| Staff | No | No | Assigned Branch | No | No | Future API | Future API |
| Member | No | No | Member Scope | No | No | Future API | Future API |

---

## Important Commands

Clear caches:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

Fresh database:

```bash
php artisan migrate:fresh --seed
```

Build frontend:

```bash
npm run build
```

Development frontend:

```bash
npm run dev
```

PHP syntax check sample:

```bash
find app routes database bootstrap -name "*.php" -print0 | xargs -0 -n1 php -l
```

---

## Development Notes

- Jangan aktifkan login admin untuk Staff dan Member.
- Categories hanya boleh dikelola Super Admin.
- Admin Branch wajib branch-scoped.
- Upload image admin harus melalui file upload, bukan input URL.
- Seeder boleh memakai dummy external URL dengan flag `*_is_external`.
- Logic bisnis sebaiknya tetap berada di `app/Services`, bukan di controller.
- Controller idealnya hanya handle request, validation, dan response.
- Future API dapat memakai service layer yang sama agar tidak duplikasi logic.

---

## Troubleshooting

### Login gagal walaupun user ada

Reset password via Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'super@lumina.ac.id')->first();

$user->forceFill([
    'password_hash' => Hash::make('password'),
    'is_active' => true,
    'is_verified' => true,
])->save();

Hash::check('password', $user->fresh()->password_hash);
```

### Map marker tidak muncul

Run ulang build dan hard refresh browser:

```bash
npm run build
php artisan optimize:clear
```

Browser:

```txt
Ctrl + F5
```

### Uploaded images tidak tampil

Pastikan storage link sudah dibuat:

```bash
php artisan storage:link
```

### Route/view cache bermasalah

```bash
php artisan optimize:clear
php artisan view:clear
```

---

## Author

**Reza Maulana**  
**RM Template**  
Website: [https://rmtemplate.com](https://rmtemplate.com)

## License

This project is licensed under the **RM Template License**.

Copyright (c) 2026 **Reza Maulana / RM Template**.

All rights reserved. This project, source code, UI template, design assets, and documentation are provided as part of RM Template work and may only be used, modified, or distributed according to permission from **Reza Maulana / RM Template**.

For license inquiries, customization, or commercial usage, visit [https://rmtemplate.com](https://rmtemplate.com).
