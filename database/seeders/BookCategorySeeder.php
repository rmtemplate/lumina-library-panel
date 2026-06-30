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
            // ── Core 8 (existing) ──────────────────────────────────────────
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000001', 'code' => 'fiksi',       'slug' => 'fiksi-sastra',           'name' => 'Fiksi & Sastra',           'icon' => 'auto_stories',    'img' => $base.'1512820790803-83ca734da794?auto=format&fit=crop&w=600&q=80', 'desc' => 'Novel, cerpen, puisi, dan karya sastra lainnya.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000002', 'code' => 'sains',       'slug' => 'sains-matematika',       'name' => 'Sains & Matematika',       'icon' => 'science',         'img' => $base.'1532094349884-543bc11b234d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Fisika, kimia, biologi, matematika, dan ilmu alam.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000003', 'code' => 'teknologi',   'slug' => 'teknologi-komputer',     'name' => 'Teknologi & Komputer',     'icon' => 'computer',        'img' => $base.'1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80', 'desc' => 'Programming, software engineering, AI, database, networking, dan teknologi informasi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000004', 'code' => 'sejarah',     'slug' => 'sejarah-budaya',         'name' => 'Sejarah & Budaya',         'icon' => 'history_edu',     'img' => $base.'1481627834876-b7833e8f5570?auto=format&fit=crop&w=600&q=80', 'desc' => 'Sejarah dunia, Indonesia, antropologi, dan studi budaya.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000005', 'code' => 'bisnis',      'slug' => 'bisnis-ekonomi',         'name' => 'Bisnis & Ekonomi',         'icon' => 'business_center', 'img' => $base.'1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80', 'desc' => 'Manajemen, ekonomi, keuangan, pajak, audit, marketing, dan entrepreneurship.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000006', 'code' => 'psikologi',   'slug' => 'psikologi-self-help',    'name' => 'Psikologi & Self-Help',    'icon' => 'psychology',      'img' => $base.'1495446815901-a7297e633e8d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Psikologi, pengembangan diri, kebiasaan, dan motivasi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000007', 'code' => 'agama',       'slug' => 'agama-filsafat',         'name' => 'Agama & Filsafat',         'icon' => 'menu_book',       'img' => $base.'1521587760476-6c12a4b040da?auto=format&fit=crop&w=600&q=80', 'desc' => 'Studi agama, filsafat, etika, dan spiritualitas.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000008', 'code' => 'referensi',   'slug' => 'referensi-ensiklopedia', 'name' => 'Referensi & Ensiklopedia', 'icon' => 'library_books',   'img' => $base.'1507842217343-583bb7270b66?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kamus, ensiklopedia, atlas, dan buku referensi umum.'],

            // ── Extended categories ────────────────────────────────────────
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000009', 'code' => 'pemrograman', 'slug' => 'pemrograman',            'name' => 'Pemrograman',              'icon' => 'code',            'img' => $base.'1555066931-bf19f8fd9fab?auto=format&fit=crop&w=600&q=80', 'desc' => 'Algoritma, struktur data, Python, Java, JavaScript, Go, Rust, dan bahasa pemrograman lainnya.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000010', 'code' => 'ai',          'slug' => 'kecerdasan-buatan',      'name' => 'Kecerdasan Buatan',        'icon' => 'smart_toy',       'img' => $base.'1677442135703-1f9b4ead6c2b?auto=format&fit=crop&w=600&q=80', 'desc' => 'AI, machine learning, deep learning, neural networks, dan computer vision.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000011', 'code' => 'biografi',    'slug' => 'biografi-memoar',        'name' => 'Biografi & Memoar',        'icon' => 'person',          'img' => $base.'1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kisah nyata tokoh-tokoh berpengaruh dunia dan Indonesia.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000012', 'code' => 'manajemen',   'slug' => 'manajemen-kepemimpinan', 'name' => 'Manajemen & Kepemimpinan', 'icon' => 'groups',          'img' => $base.'1552664730-d307ca884978?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kepemimpinan, manajemen strategis, organisasi, dan pengembangan tim.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000013', 'code' => 'pendidikan',  'slug' => 'pendidikan',             'name' => 'Pendidikan',               'icon' => 'school',          'img' => $base.'1503676260728-1c00da094a0b?auto=format&fit=crop&w=600&q=80', 'desc' => 'Metodologi pengajaran, kurikulum, psikologi pendidikan, dan pedagogi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000014', 'code' => 'kesehatan',   'slug' => 'kesehatan-medis',        'name' => 'Kesehatan & Medis',        'icon' => 'health_and_safety','img' => $base.'1576091160399-112ba8d25d1d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Ilmu kedokteran, keperawatan, nutrisi, kesehatan mental, dan gaya hidup sehat.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000015', 'code' => 'hukum',       'slug' => 'hukum-politik',          'name' => 'Hukum & Politik',          'icon' => 'gavel',           'img' => $base.'1589829545856-d10d557cf95f?auto=format&fit=crop&w=600&q=80', 'desc' => 'Ilmu hukum, perundang-undangan, ilmu politik, dan pemerintahan.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000016', 'code' => 'matematika',  'slug' => 'matematika-murni',       'name' => 'Matematika',               'icon' => 'calculate',       'img' => $base.'1635372722867-8a75a2b39b8f?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kalkulus, aljabar, geometri, statistika, dan matematika diskrit.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000017', 'code' => 'fisika',      'slug' => 'fisika',                 'name' => 'Fisika',                   'icon' => 'bolt',            'img' => $base.'1446776811571-c3ccc4f55e50?auto=format&fit=crop&w=600&q=80', 'desc' => 'Mekanika, termodinamika, elektromagnetisme, fisika modern, dan fisika kuantum.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000018', 'code' => 'kimia',       'slug' => 'kimia',                  'name' => 'Kimia',                    'icon' => 'science',         'img' => $base.'1532187863486-abf9dbad1b69?auto=format&fit=crop&w=600&q=80', 'desc' => 'Kimia organik, anorganik, fisika kimia, dan kimia analitik.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000019', 'code' => 'biologi',     'slug' => 'biologi',                'name' => 'Biologi',                  'icon' => 'biotech',         'img' => $base.'1530587191b3-c49a7e7d4b21?auto=format&fit=crop&w=600&q=80', 'desc' => 'Biologi sel, genetika, ekologi, evolusi, dan biologi molekuler.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000020', 'code' => 'seni',        'slug' => 'seni-desain',            'name' => 'Seni & Desain',            'icon' => 'palette',         'img' => $base.'1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Desain grafis, ilustrasi, seni rupa, fotografi, dan desain UI/UX.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000021', 'code' => 'bahasa',      'slug' => 'bahasa-linguistik',      'name' => 'Bahasa & Linguistik',      'icon' => 'translate',       'img' => $base.'1546410531-b0a4e9cf6f91?auto=format&fit=crop&w=600&q=80', 'desc' => 'Linguistik, belajar bahasa asing, tata bahasa, dan komunikasi.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000022', 'code' => 'sastra-id',   'slug' => 'sastra-indonesia',       'name' => 'Sastra Indonesia',         'icon' => 'history_edu',     'img' => $base.'1544947950-fa07a98d237f?auto=format&fit=crop&w=600&q=80', 'desc' => 'Karya sastra klasik dan modern Indonesia — Pram, Hamka, Chairil Anwar, dll.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000023', 'code' => 'anak',        'slug' => 'buku-anak',              'name' => 'Buku Anak',                'icon' => 'child_care',      'img' => $base.'1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=600&q=80', 'desc' => 'Buku cerita, dongeng, dan edukatif untuk anak usia 3-12 tahun.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000024', 'code' => 'komik',       'slug' => 'komik-manga',            'name' => 'Komik & Manga',            'icon' => 'import_contacts', 'img' => $base.'1578632292916-9a5f2a1aa98f?auto=format&fit=crop&w=600&q=80', 'desc' => 'Komik lokal, manga Jepang, manhwa Korea, dan novel grafis.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000025', 'code' => 'arsitektur',  'slug' => 'arsitektur',             'name' => 'Arsitektur',               'icon' => 'architecture',    'img' => $base.'1486325212027-8081e485255e?auto=format&fit=crop&w=600&q=80', 'desc' => 'Desain arsitektur, konstruksi, perencanaan kota, dan interior.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000026', 'code' => 'agama-islam', 'slug' => 'agama-islam',            'name' => 'Agama Islam',              'icon' => 'menu_book',       'img' => $base.'1585036156171-384164a392d9?auto=format&fit=crop&w=600&q=80', 'desc' => 'Al-Quran, Hadits, Fiqh, Sejarah Islam, dan kajian keislaman.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000027', 'code' => 'novel-world',  'slug' => 'novel-terjemahan',      'name' => 'Novel Terjemahan',         'icon' => 'auto_stories',    'img' => $base.'1544947950-fa07a98d237f?auto=format&fit=crop&w=600&q=80', 'desc' => 'Novel-novel terbaik dunia dalam terjemahan Bahasa Indonesia.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000028', 'code' => 'kewirausahaan','slug' => 'kewirausahaan',         'name' => 'Kewirausahaan',            'icon' => 'rocket_launch',   'img' => $base.'1521791055903-6a8301cbbf78?auto=format&fit=crop&w=600&q=80', 'desc' => 'Startup, inovasi, entrepreneurship, dan kisah sukses wirausahawan.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000029', 'code' => 'lingkungan',  'slug' => 'lingkungan-hidup',       'name' => 'Lingkungan Hidup',         'icon' => 'eco',             'img' => $base.'1441974231531-c6227db76b6e?auto=format&fit=crop&w=600&q=80', 'desc' => 'Ekologi, perubahan iklim, keberlanjutan, dan lingkungan hidup.'],
            ['id' => 'e1b2c3d4-0005-4000-8000-000000000030', 'code' => 'filsafat',    'slug' => 'filsafat',               'name' => 'Filsafat',                 'icon' => 'lightbulb',       'img' => $base.'1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80', 'desc' => 'Filsafat Barat, Timur, etika, logika, dan metafisika.'],
        ];

        foreach ($categories as $i => $c) {
            DB::table('book_categories')->insertOrIgnore([
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
