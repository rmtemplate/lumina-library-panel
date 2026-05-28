<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserSessionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('user_sessions')->insert([
            // Budi: active session
            [
                'id'               => Str::uuid()->toString(),
                'user_id'          => 'c1b2c3d4-0003-4000-8000-000000000001',
                'token_hash'       => hash('sha256', 'budi-session-token-' . Str::random(32)),
                'device_info'      => 'Samsung Galaxy S23 Ultra | Android 14 | Lumina Library v1.0.0',
                'ip_address'       => '192.168.1.100',
                'expires_at'       => $now->copy()->addDays(30),
                'last_activity_at' => $now,
                'is_active'        => true,
                'created_at'       => $now,
            ],
            // Sarah: active session
            [
                'id'               => Str::uuid()->toString(),
                'user_id'          => 'c1b2c3d4-0003-4000-8000-000000000004',
                'token_hash'       => hash('sha256', 'sarah-session-token-' . Str::random(32)),
                'device_info'      => 'iPhone 15 Pro | iOS 17.5 | Lumina Library v1.0.0',
                'ip_address'       => '192.168.1.101',
                'expires_at'       => $now->copy()->addDays(30),
                'last_activity_at' => $now,
                'is_active'        => true,
                'created_at'       => $now,
            ],
            // Ahmad: active session
            [
                'id'               => Str::uuid()->toString(),
                'user_id'          => 'c1b2c3d4-0003-4000-8000-000000000005',
                'token_hash'       => hash('sha256', 'ahmad-session-token-' . Str::random(32)),
                'device_info'      => 'Xiaomi 14 | Android 14 | Lumina Library v1.0.0',
                'ip_address'       => '192.168.1.102',
                'expires_at'       => $now->copy()->addDays(30),
                'last_activity_at' => $now->copy()->subHours(3),
                'is_active'        => true,
                'created_at'       => $now->copy()->subDays(5),
            ],
            // Rina: EXPIRED session (menunjukkan session yang sudah habis masa berlaku)
            [
                'id'               => Str::uuid()->toString(),
                'user_id'          => 'c1b2c3d4-0003-4000-8000-000000000002',
                'token_hash'       => hash('sha256', 'rina-expired-token-' . Str::random(32)),
                'device_info'      => 'OPPO Reno 11 | Android 14 | Lumina Library v1.0.0',
                'ip_address'       => '192.168.1.103',
                'expires_at'       => $now->copy()->subDay(), // expired yesterday
                'last_activity_at' => $now->copy()->subDays(2),
                'is_active'        => false,
                'created_at'       => $now->copy()->subDays(31),
            ],
        ]);
    }
}
