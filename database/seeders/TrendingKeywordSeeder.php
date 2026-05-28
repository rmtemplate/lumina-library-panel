<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TrendingKeywordSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $keywords = [
            ['keyword' => 'clean code',      'search_count' => 45, 'rank' => 1],
            ['keyword' => 'sapiens',         'search_count' => 38, 'rank' => 2],
            ['keyword' => 'atomic habits',   'search_count' => 32, 'rank' => 3],
            ['keyword' => 'novel indonesia', 'search_count' => 28, 'rank' => 4],
            ['keyword' => 'filosofi',        'search_count' => 22, 'rank' => 5],
        ];

        foreach ($keywords as $k) {
            DB::table('trending_keywords')->insert([
                'id'           => Str::uuid()->toString(),
                'keyword'      => $k['keyword'],
                'search_count' => $k['search_count'],
                'rank_position' => $k['rank'],
                'period_start' => $now->copy()->startOfMonth()->toDateString(),
                'period_end'   => $now->copy()->endOfMonth()->toDateString(),
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }
}
