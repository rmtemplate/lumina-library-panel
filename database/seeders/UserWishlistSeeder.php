<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserWishlistSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina  = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas = 'c1b2c3d4-0003-4000-8000-000000000003';
        $book  = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);

        $wishlists = [
            [$budi,  $book(15), 5],  // Thinking Fast Slow
            [$budi,  $book(12), 8],  // Design Patterns
            [$rina,  $book(5),  3],  // Atomic Habits
            [$rina,  $book(6),  6],  // Filosofi Teras
            [$dimas, $book(8),  2],  // Brief History of Time
        ];

        foreach ($wishlists as $w) {
            DB::table('user_wishlists')->insert([
                'id'       => Str::uuid()->toString(),
                'user_id'  => $w[0],
                'book_id'  => $w[1],
                'added_at' => $now->copy()->subDays($w[2]),
            ]);
        }
    }
}
