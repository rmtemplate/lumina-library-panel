<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';
        $base = 'https://images.unsplash.com/photo-';

        $categories = [
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000001', 'code' => 'fiksi', 'slug' => 'fiksi-sastra', 'name' => 'Fiksi & Sastra', 'icon' => 'auto_stories', 'img' => $base.'1512820790803-83ca734da794?auto=format&fit=crop&w=600&q=80', 'desc' => 'Novel, cerpen, puisi, dan karya sastra lainnya.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000002', 'code' => 'sains', 'slug' => 'sains-matematika', 'name' => 'Sains & Matematika', 'icon' => 'science', 'img' => $base.'1532094349884-543bc11b234d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Fisika, kimia, biologi, matematika, dan ilmu alam.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000003', 'code' => 'teknologi', 'slug' => 'teknologi-komputer', 'name' => 'Teknologi & Komputer', 'icon' => 'computer', 'img' => $base.'1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80', 'desc' => 'Programming, software engineering, AI, database, networking, dan teknologi informasi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000004', 'code' => 'sejarah', 'slug' => 'sejarah-budaya', 'name' => 'Sejarah & Budaya', 'icon' => 'history_edu', 'img' => $base.'1481627834876-b7833e8f5570?auto=format&fit=crop&w=600&q=80', 'desc' => 'Sejarah dunia, Indonesia, antropologi, dan studi budaya.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000005', 'code' => 'bisnis', 'slug' => 'bisnis-ekonomi', 'name' => 'Bisnis & Ekonomi', 'icon' => 'business_center', 'img' => $base.'1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80', 'desc' => 'Manajemen, ekonomi, keuangan, pajak, audit, marketing, dan entrepreneurship.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000006', 'code' => 'psikologi', 'slug' => 'psikologi-self-help', 'name' => 'Psikologi & Self-Help', 'icon' => 'psychology', 'img' => $base.'1495446815901-a7297e633e8d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Psikologi, pengembangan diri, kebiasaan, dan motivasi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000007', 'code' => 'agama', 'slug' => 'agama-filsafat', 'name' => 'Agama & Filsafat', 'icon' => 'menu_book', 'img' => $base.'1521587760476-6c12a4b040da?auto=format&fit=crop&w=600&q=80', 'desc' => 'Studi agama, filsafat, etika, dan spiritualitas.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000008', 'code' => 'referensi', 'slug' => 'referensi-ensiklopedia', 'name' => 'Referensi & Ensiklopedia', 'icon' => 'library_books', 'img' => $base.'1507842217343-583bb7270b66?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kamus, ensiklopedia, atlas, dan buku referensi umum.'],
        ];

        foreach ($categories as $i => $c) {
            DB::table('book_categories')->insert([
                'id' => $c['id'],
                'code' => $c['code'],
                'slug' => $c['slug'],
                'name' => $c['name'],
                'description' => $c['desc'],
                'icon_name' => $c['icon'],
                'image_path' => $c['img'],
                'image_is_external' => true,
                'sort_order' => $i + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => $admin,
                'updated_by' => $admin,
            ]);
        }
    }
}
