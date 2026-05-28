<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookViewSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina  = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas = 'c1b2c3d4-0003-4000-8000-000000000003';
        $book  = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);

        $views = [
            [$budi,  $book(3),  1],  // Sapiens
            [$budi,  $book(4),  2],  // Clean Code
            [$budi,  $book(5),  3],  // Atomic Habits
            [$budi,  $book(2),  5],  // Bumi Manusia
            [$rina,  $book(1),  2],  // Laskar Pelangi
            [$rina,  $book(6),  4],  // Filosofi Teras
            [$rina,  $book(10), 6],  // Laut Bercerita
            [$dimas, $book(4),  1],  // Clean Code
            [$dimas, $book(12), 2],  // Design Patterns
            [$dimas, $book(9),  3],  // Pragmatic Programmer
        ];

        foreach ($views as $v) {
            DB::table('book_views')->insert([
                'id'         => Str::uuid()->toString(),
                'user_id'    => $v[0],
                'book_id'    => $v[1],
                'viewed_at'  => $now->copy()->subDays($v[2]),
            ]);
        }
    }
}
