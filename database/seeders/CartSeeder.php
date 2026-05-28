<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('carts')->insert([
            [
                'id'         => 'a6b2c3d4-000b-4000-8000-000000000001',
                'user_id'    => 'c1b2c3d4-0003-4000-8000-000000000001', // Budi
                'created_at' => $now->copy()->subDays(3),
                'updated_at' => $now,
            ],
            [
                'id'         => 'a6b2c3d4-000b-4000-8000-000000000002',
                'user_id'    => 'c1b2c3d4-0003-4000-8000-000000000002', // Rina
                'created_at' => $now->copy()->subDays(5),
                'updated_at' => $now,
            ],
        ]);
    }
}
