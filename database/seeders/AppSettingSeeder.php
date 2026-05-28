<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $adminId = 'c1b2c3d4-0003-4000-8000-000000000006';

        $settings = [
            ['key' => 'max_borrow_days',        'value' => '14',    'type' => 'int',     'desc' => 'Maksimal hari peminjaman buku'],
            ['key' => 'max_borrow_books',       'value' => '5',     'type' => 'int',     'desc' => 'Maksimal jumlah buku yang bisa dipinjam sekaligus'],
            ['key' => 'max_renewal_count',      'value' => '2',     'type' => 'int',     'desc' => 'Maksimal perpanjangan per peminjaman'],
            ['key' => 'renewal_extend_days',    'value' => '7',     'type' => 'int',     'desc' => 'Jumlah hari tambahan per perpanjangan'],
            ['key' => 'fine_per_day',           'value' => '1000',  'type' => 'decimal', 'desc' => 'Denda keterlambatan per hari (Rupiah)'],
            ['key' => 'reservation_hold_hours', 'value' => '24',    'type' => 'int',     'desc' => 'Lama buku di-hold untuk reservasi (jam)'],
            ['key' => 'max_cart_items',         'value' => '10',    'type' => 'int',     'desc' => 'Maksimal item dalam cart'],
            ['key' => 'password_min_length',    'value' => '6',     'type' => 'int',     'desc' => 'Minimal panjang password'],
            ['key' => 'session_lifetime_days',  'value' => '30',    'type' => 'int',     'desc' => 'Masa berlaku session login (hari)'],
            ['key' => 'library_name',           'value' => 'Lumina Library', 'type' => 'string', 'desc' => 'Nama perpustakaan'],
        ];

        foreach ($settings as $s) {
            DB::table('app_settings')->insert([
                'id'            => Str::uuid()->toString(),
                'setting_key'   => $s['key'],
                'setting_value' => $s['value'],
                'data_type'     => $s['type'],
                'description'   => $s['desc'],
                'updated_at'    => $now,
                'updated_by'    => $adminId,
            ]);
        }
    }
}
