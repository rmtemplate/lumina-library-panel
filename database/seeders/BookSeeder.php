<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 15 buku dengan data realistis.
 *
 * Mapping buku ke borrowing:
 *   - f..001 Laskar Pelangi → dipinjam Rina (active)
 *   - f..002 Bumi Manusia  → sudah dikembalikan Budi (returned)
 *   - f..003 Sapiens        → di cart Budi (pending checkout), direservasi Dimas
 *   - f..004 Clean Code     → dipinjam Dimas (active)
 *   - f..005 Atomic Habits  → di cart Budi (pending checkout)
 *   - f..006 Filosofi Teras → overdue Putri, di cart Rina
 *   - f..009 Pragmatic Prog → dipinjam Dimas (active)
 *   - f..010 Laut Bercerita → overdue Putri
 */
class BookSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';

        // Category shortcuts
        $fiksi     = 'e1b2c3d4-0005-4000-8000-000000000001';
        $sains     = 'e1b2c3d4-0005-4000-8000-000000000002';
        $teknologi = 'e1b2c3d4-0005-4000-8000-000000000003';
        $sejarah   = 'e1b2c3d4-0005-4000-8000-000000000004';
        $bisnis    = 'e1b2c3d4-0005-4000-8000-000000000005';
        $psikologi = 'e1b2c3d4-0005-4000-8000-000000000006';
        $agama     = 'e1b2c3d4-0005-4000-8000-000000000007';
        $referensi = 'e1b2c3d4-0005-4000-8000-000000000008';

        $books = [
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000001',
                'title'        => 'Laskar Pelangi',
                'author'       => 'Andrea Hirata',
                'isbn'         => '978-602-291-000-1',
                'publisher'    => 'Bentang Pustaka',
                'publish_year' => 2005,
                'description'  => 'Kisah inspiratif 10 anak dari Belitung yang berjuang demi pendidikan. Novel fenomenal yang telah diterjemahkan ke lebih dari 30 bahasa dan menjadi film layar lebar.',
                'cover_path'    => 'https://picsum.photos/seed/laskar-pelangi/300/450',
                'cover_is_external' => true,
                'category_id'  => $fiksi,
                'language'     => 'Indonesia',
                'page_count'   => 529,
                'edition'      => 'Cetakan ke-50',
                'rating'       => 4.80,
                'rating_count' => 1250,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000002',
                'title'        => 'Bumi Manusia',
                'author'       => 'Pramoedya Ananta Toer',
                'isbn'         => '978-979-073-005-2',
                'publisher'    => 'Hasta Mitra',
                'publish_year' => 1980,
                'description'  => 'Tetralogi Buru pertama karya Pram. Menggambarkan kehidupan di Hindia Belanda awal abad ke-20 melalui kisah Minke dan Nyai Ontosoroh.',
                'cover_path'    => 'https://picsum.photos/seed/bumi-manusia/300/450',
                'cover_is_external' => true,
                'category_id'  => $fiksi,
                'language'     => 'Indonesia',
                'page_count'   => 535,
                'edition'      => 'Edisi Baru',
                'rating'       => 4.90,
                'rating_count' => 2100,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000003',
                'title'        => 'Sapiens: A Brief History of Humankind',
                'author'       => 'Yuval Noah Harari',
                'isbn'         => '978-0-06-231609-7',
                'publisher'    => 'Harper',
                'publish_year' => 2011,
                'description'  => 'Perjalanan epik sejarah umat manusia dari Homo sapiens di Afrika hingga revolusi ilmiah. Buku bestseller internasional yang menantang cara kita memahami dunia.',
                'cover_path'    => 'https://picsum.photos/seed/sapiens/300/450',
                'cover_is_external' => true,
                'category_id'  => $sejarah,
                'language'     => 'English',
                'page_count'   => 464,
                'edition'      => '10th Anniversary',
                'rating'       => 4.70,
                'rating_count' => 890,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000004',
                'title'        => 'Clean Code: A Handbook of Agile Software Craftsmanship',
                'author'       => 'Robert C. Martin',
                'isbn'         => '978-0-13-235088-4',
                'publisher'    => 'Prentice Hall',
                'publish_year' => 2008,
                'description'  => 'Panduan menulis kode yang bersih, mudah dibaca, dan mudah di-maintain. Buku wajib bagi programmer yang ingin meningkatkan kualitas kodenya.',
                'cover_path'    => 'https://picsum.photos/seed/clean-code/300/450',
                'cover_is_external' => true,
                'category_id'  => $teknologi,
                'language'     => 'English',
                'page_count'   => 431,
                'edition'      => '1st Edition',
                'rating'       => 4.60,
                'rating_count' => 780,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000005',
                'title'        => 'Atomic Habits',
                'author'       => 'James Clear',
                'isbn'         => '978-0-7352-1129-2',
                'publisher'    => 'Avery',
                'publish_year' => 2018,
                'description'  => 'Cara mudah dan terbukti untuk membangun kebiasaan baik dan menghilangkan kebiasaan buruk. Perubahan kecil yang memberikan hasil luar biasa.',
                'cover_path'    => 'https://picsum.photos/seed/atomic-habits/300/450',
                'cover_is_external' => true,
                'category_id'  => $psikologi,
                'language'     => 'English',
                'page_count'   => 320,
                'edition'      => '1st Edition',
                'rating'       => 4.80,
                'rating_count' => 1500,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000006',
                'title'        => 'Filosofi Teras',
                'author'       => 'Henry Manampiring',
                'isbn'         => '978-602-291-528-0',
                'publisher'    => 'Kompas',
                'publish_year' => 2018,
                'description'  => 'Filsafat Stoa yang dipraktikkan dalam kehidupan sehari-hari. Buku self-help berbasis filosofi kuno yang relevan untuk menghadapi masalah modern.',
                'cover_path'    => 'https://picsum.photos/seed/filosofi-teras/300/450',
                'cover_is_external' => true,
                'category_id'  => $psikologi,
                'language'     => 'Indonesia',
                'page_count'   => 346,
                'edition'      => 'Cetakan ke-15',
                'rating'       => 4.50,
                'rating_count' => 620,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000007',
                'title'        => 'Ekonomi Indonesia: Dalam Lintasan Sejarah',
                'author'       => 'Sri Mulyani Indrawati',
                'isbn'         => '978-602-xxx-007-7',
                'publisher'    => 'Gramedia Pustaka Utama',
                'publish_year' => 2020,
                'description'  => 'Analisis mendalam tentang perjalanan ekonomi Indonesia dari era kolonial hingga era digital oleh Menteri Keuangan RI.',
                'cover_path'    => 'https://picsum.photos/seed/ekonomi-indonesia/300/450',
                'cover_is_external' => true,
                'category_id'  => $bisnis,
                'language'     => 'Indonesia',
                'page_count'   => 412,
                'edition'      => '1st Edition',
                'rating'       => 4.30,
                'rating_count' => 340,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000008',
                'title'        => 'A Brief History of Time',
                'author'       => 'Stephen Hawking',
                'isbn'         => '978-0-553-10953-5',
                'publisher'    => 'Bantam Books',
                'publish_year' => 1988,
                'description'  => 'Dari Big Bang hingga Black Hole. Stephen Hawking menjelaskan konsep kosmologi kompleks dalam bahasa yang bisa dipahami semua orang.',
                'cover_path'    => 'https://picsum.photos/seed/brief-history-time/300/450',
                'cover_is_external' => true,
                'category_id'  => $sains,
                'language'     => 'English',
                'page_count'   => 256,
                'edition'      => 'Updated Edition',
                'rating'       => 4.70,
                'rating_count' => 950,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000009',
                'title'        => 'The Pragmatic Programmer: From Journeyman to Master',
                'author'       => 'Andrew Hunt, David Thomas',
                'isbn'         => '978-0-13-595705-9',
                'publisher'    => 'Addison-Wesley',
                'publish_year' => 1999,
                'description'  => 'Kumpulan tips dan praktik terbaik untuk menjadi programmer yang lebih efektif. Edisi ke-20 anniversary dengan konten yang diperbarui.',
                'cover_path'    => 'https://picsum.photos/seed/pragmatic-programmer/300/450',
                'cover_is_external' => true,
                'category_id'  => $teknologi,
                'language'     => 'English',
                'page_count'   => 352,
                'edition'      => '20th Anniversary Edition',
                'rating'       => 4.50,
                'rating_count' => 680,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000010',
                'title'        => 'Laut Bercerita',
                'author'       => 'Leila S. Chudori',
                'isbn'         => '978-602-291-478-8',
                'publisher'    => 'Kepustakaan Populer Gramedia',
                'publish_year' => 2017,
                'description'  => 'Novel tentang aktivis mahasiswa tahun 1998. Kisah cinta, persahabatan, dan perjuangan melawan ketidakadilan yang penuh emosi.',
                'cover_path'    => 'https://picsum.photos/seed/laut-bercerita/300/450',
                'cover_is_external' => true,
                'category_id'  => $fiksi,
                'language'     => 'Indonesia',
                'page_count'   => 394,
                'edition'      => 'Cetakan ke-8',
                'rating'       => 4.60,
                'rating_count' => 530,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000011',
                'title'        => 'Sejarah Indonesia Modern 1200-2008',
                'author'       => 'M.C. Ricklefs',
                'isbn'         => '978-979-461-765-8',
                'publisher'    => 'Serambi',
                'publish_year' => 2005,
                'description'  => 'Buku referensi standar tentang sejarah Indonesia dari masa kerajaan hingga era reformasi oleh sejarawan Australia terkemuka.',
                'cover_path'    => 'https://picsum.photos/seed/sejarah-indonesia/300/450',
                'cover_is_external' => true,
                'category_id'  => $sejarah,
                'language'     => 'Indonesia',
                'page_count'   => 672,
                'edition'      => 'Edisi Terbaru',
                'rating'       => 4.20,
                'rating_count' => 280,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000012',
                'title'        => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                'author'       => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
                'isbn'         => '978-0-201-63361-0',
                'publisher'    => 'Addison-Wesley',
                'publish_year' => 1994,
                'description'  => 'Buku klasik Gang of Four (GoF) yang mendefinisikan 23 design pattern fundamental dalam software engineering.',
                'cover_path'    => 'https://picsum.photos/seed/design-patterns/300/450',
                'cover_is_external' => true,
                'category_id'  => $teknologi,
                'language'     => 'English',
                'page_count'   => 395,
                'edition'      => '1st Edition',
                'rating'       => 4.40,
                'rating_count' => 560,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000013',
                'title'        => 'Kitab Tauhid',
                'author'       => 'Muhammad bin Abdul Wahhab',
                'isbn'         => '978-602-xxx-013-8',
                'publisher'    => 'Darul Haq',
                'publish_year' => 2010,
                'description'  => 'Pembahasan mendasar tentang tauhid dan aqidah Islam. Kitab klasik yang menjadi rujukan utama dalam studi ilmu tauhid.',
                'cover_path'    => 'https://picsum.photos/seed/kitab-tauhid/300/450',
                'cover_is_external' => true,
                'category_id'  => $agama,
                'language'     => 'Indonesia',
                'page_count'   => 280,
                'edition'      => 'Terjemahan Baru',
                'rating'       => 4.50,
                'rating_count' => 420,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000014',
                'title'        => 'Kamus Besar Bahasa Indonesia (KBBI)',
                'author'       => 'Tim Penyusun Pusat Bahasa',
                'isbn'         => '978-979-069-090-5',
                'publisher'    => 'Balai Pustaka',
                'publish_year' => 2016,
                'description'  => 'Kamus resmi bahasa Indonesia edisi kelima. Memuat lebih dari 127.000 lema dan merupakan rujukan utama ejaan dan makna kata bahasa Indonesia.',
                'cover_path'    => 'https://picsum.photos/seed/kbbi/300/450',
                'cover_is_external' => true,
                'category_id'  => $referensi,
                'language'     => 'Indonesia',
                'page_count'   => 1820,
                'edition'      => 'Edisi V',
                'rating'       => 4.00,
                'rating_count' => 150,
            ],
            [
                'id'           => 'f1b2c3d4-0006-4000-8000-000000000015',
                'title'        => 'Thinking, Fast and Slow',
                'author'       => 'Daniel Kahneman',
                'isbn'         => '978-0-374-53355-7',
                'publisher'    => 'Farrar, Straus and Giroux',
                'publish_year' => 2011,
                'description'  => 'Pemenang Nobel Daniel Kahneman mengungkap dua sistem berpikir yang mendorong keputusan kita: Sistem 1 (cepat, intuitif) dan Sistem 2 (lambat, analitis).',
                'cover_path'    => 'https://picsum.photos/seed/thinking-fast-slow/300/450',
                'cover_is_external' => true,
                'category_id'  => $psikologi,
                'language'     => 'English',
                'page_count'   => 499,
                'edition'      => '1st Edition',
                'rating'       => 4.60,
                'rating_count' => 720,
            ],
        ];

        foreach ($books as $b) {

            DB::table('books')->insert(array_merge($b, [
                'is_active'  => true,
                'created_at' => $now->copy()->subMonths(3),
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => $admin,
                'updated_by' => $admin,
            ]));
        }
    }
}
