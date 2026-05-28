<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $users = [
            'c1b2c3d4-0003-4000-8000-000000000001', // Budi
            'c1b2c3d4-0003-4000-8000-000000000002', // Rina
            'c1b2c3d4-0003-4000-8000-000000000003', // Dimas
            'c1b2c3d4-0003-4000-8000-000000000004', // Sarah
            'c1b2c3d4-0003-4000-8000-000000000005', // Ahmad
            'c1b2c3d4-0003-4000-8000-000000000006', // Admin
            'c1b2c3d4-0003-4000-8000-000000000007', // Putri
            'c1b2c3d4-0003-4000-8000-000000000008', // Reza
        ];

        foreach ($users as $userId) {
            $isReza = ($userId === 'c1b2c3d4-0003-4000-8000-000000000008');

            DB::table('notification_preferences')->insert([
                'id'                      => Str::uuid()->toString(),
                'user_id'                 => $userId,
                'borrowing_reminder'      => true,
                'overdue_alert'           => true,
                'return_confirmation'     => true,
                'fine_notice'             => true,
                'reservation_available'   => true,
                'system_announcement'     => true,
                'push_enabled'            => true,
                'email_enabled'           => $isReza, // Reza (pekerja) mau email juga
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }
    }
}
