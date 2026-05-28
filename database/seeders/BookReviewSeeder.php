<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookReviewSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina  = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas = 'c1b2c3d4-0003-4000-8000-000000000003';
        $reza  = 'c1b2c3d4-0003-4000-8000-000000000008';
        $book  = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);

        DB::table('book_reviews')->insert([
            [
                'id'                   => Str::uuid()->toString(),
                'user_id'              => $budi,
                'book_id'              => $book(2), // Bumi Manusia
                'rating'               => 5,
                'review_text'          => 'Karya sastra luar biasa. Pramoedya berhasil menggambarkan era kolonial dengan sangat detail dan emosional. Wajib baca untuk semua orang Indonesia.',
                'is_verified_borrower' => true, // Budi memang pernah pinjam buku ini
                'is_visible'           => true,
                'created_at' => $now->copy()->subDays(15), 'updated_at' => $now->copy()->subDays(15), 'deleted_at' => null,
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'user_id'              => $rina,
                'book_id'              => $book(1), // Laskar Pelangi
                'rating'               => 5,
                'review_text'          => 'Sangat menginspirasi! Cerita yang menyentuh hati tentang semangat belajar anak-anak Belitung. Membuat saya lebih bersyukur dengan kesempatan pendidikan.',
                'is_verified_borrower' => true, // Rina sedang pinjam buku ini
                'is_visible'           => true,
                'created_at' => $now->copy()->subDays(8), 'updated_at' => $now->copy()->subDays(8), 'deleted_at' => null,
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'user_id'              => $reza,
                'book_id'              => $book(4), // Clean Code
                'rating'               => 4,
                'review_text'          => 'Wajib baca untuk programmer. Prinsip-prinsipnya sangat relevan meskipun buku ini sudah cukup lama. Beberapa contoh Java agak outdated, tapi konsepnya timeless.',
                'is_verified_borrower' => false, // Reza belum pernah pinjam, baca di tempat lain
                'is_visible'           => true,
                'created_at' => $now->copy()->subDays(12), 'updated_at' => $now->copy()->subDays(12), 'deleted_at' => null,
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'user_id'              => $dimas,
                'book_id'              => $book(5), // Atomic Habits
                'rating'               => 5,
                'review_text'          => 'Buku yang mengubah cara pandang tentang kebiasaan. Setelah baca ini, saya mulai habit tracking dan hasilnya luar biasa!',
                'is_verified_borrower' => false,
                'is_visible'           => true,
                'created_at' => $now->copy()->subDays(5), 'updated_at' => $now->copy()->subDays(5), 'deleted_at' => null,
            ],
        ]);
    }
}
