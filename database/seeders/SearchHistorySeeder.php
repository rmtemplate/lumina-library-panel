<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SearchHistorySeeder extends Seeder
{
    public function run(): void
    {
        $now  = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $rina  = 'c1b2c3d4-0003-4000-8000-000000000002';
        $dimas = 'c1b2c3d4-0003-4000-8000-000000000003';
        $putri = 'c1b2c3d4-0003-4000-8000-000000000007';

        $searches = [
            [$budi,  'clean code',           3],
            [$budi,  'sapiens',              5],
            [$budi,  'atomic habits',        7],
            [$rina,  'laskar pelangi',       2],
            [$rina,  'novel indonesia',      4],
            [$dimas, 'design patterns',      1],
            [$dimas, 'pragmatic programmer', 3],
            [$putri, 'filosofi',             8],
        ];

        foreach ($searches as $s) {
            DB::table('search_histories')->insert([
                'id'         => Str::uuid()->toString(),
                'user_id'    => $s[0],
                'keyword'    => $s[1],
                'searched_at' => $now->copy()->subDays($s[2]),
            ]);
        }
    }
}
