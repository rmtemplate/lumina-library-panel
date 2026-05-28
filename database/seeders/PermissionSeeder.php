<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 20 permissions across 7 modules.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $i = 0;
        $permissions = [
            // Book module
            ['code' => 'book.view',             'name' => 'Lihat Buku',                   'module' => 'book'],
            ['code' => 'book.create',            'name' => 'Tambah Buku',                  'module' => 'book'],
            ['code' => 'book.update',            'name' => 'Edit Buku',                    'module' => 'book'],
            ['code' => 'book.delete',            'name' => 'Hapus Buku',                   'module' => 'book'],
            // Borrowing module
            ['code' => 'borrowing.create',       'name' => 'Buat Peminjaman',              'module' => 'borrowing'],
            ['code' => 'borrowing.approve',      'name' => 'Setujui Peminjaman',           'module' => 'borrowing'],
            ['code' => 'borrowing.return',       'name' => 'Proses Pengembalian',          'module' => 'borrowing'],
            ['code' => 'borrowing.view_all',     'name' => 'Lihat Semua Peminjaman',       'module' => 'borrowing'],
            // Fine module
            ['code' => 'fine.create',            'name' => 'Buat Denda',                   'module' => 'fine'],
            ['code' => 'fine.waive',             'name' => 'Hapus/Waive Denda',            'module' => 'fine'],
            ['code' => 'fine.view_all',          'name' => 'Lihat Semua Denda',            'module' => 'fine'],
            // Report module
            ['code' => 'report.view',            'name' => 'Lihat Laporan',                'module' => 'report'],
            ['code' => 'report.export',          'name' => 'Export Laporan',               'module' => 'report'],
            // User module
            ['code' => 'user.view_all',          'name' => 'Lihat Semua User',             'module' => 'user'],
            ['code' => 'user.create',            'name' => 'Tambah User',                  'module' => 'user'],
            ['code' => 'user.update',            'name' => 'Edit User',                    'module' => 'user'],
            ['code' => 'user.block',             'name' => 'Blokir User',                  'module' => 'user'],
            // Rack module
            ['code' => 'rack.manage',            'name' => 'Kelola Rak',                   'module' => 'rack'],
            ['code' => 'rack.move_books',        'name' => 'Pindahkan Buku Antar Rak',     'module' => 'rack'],
            // System module
            ['code' => 'system.settings',        'name' => 'Kelola Pengaturan Sistem',     'module' => 'system'],
        ];

        foreach ($permissions as $p) {
            $i++;
            DB::table('permissions')->insert([
                'id'          => sprintf('a7b2c3d4-000c-4000-8000-%012d', $i),
                'code'        => $p['code'],
                'name'        => $p['name'],
                'description' => null,
                'module'      => $p['module'],
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
                'deleted_at'  => null,
                'created_by'  => null,
                'updated_by'  => null,
            ]);
        }
    }
}
