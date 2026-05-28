<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Belum ada pembayaran (kedua denda Putri masih unpaid).
 * File ini berisi contoh yang di-comment untuk referensi saat ngoding.
 */
class FinePaymentSeeder extends Seeder
{
    public function run(): void
    {
        // ┌──────────────────────────────────────────────────────────────┐
        // │ Saat ini belum ada pembayaran denda.                        │
        // │ Uncomment blok di bawah setelah flow pembayaran tersedia.   │
        // └──────────────────────────────────────────────────────────────┘

        /*
        $now   = Carbon::now();
        $putri = 'c1b2c3d4-0003-4000-8000-000000000007';
        $sarah = 'c1b2c3d4-0003-4000-8000-000000000004';

        // Contoh: Putri membayar denda Filosofi Teras via transfer bank
        DB::table('fine_payments')->insert([
            'id'                => Str::uuid()->toString(),
            'fine_id'           => 'a5b2c3d4-000a-4000-8000-000000000001',
            'user_id'           => $putri,
            'amount'            => 6000.00,
            'payment_method'    => 'bank_transfer',
            'payment_reference' => 'TRF-BCA-20260528-001',
            'payment_proof_path' => 'payments/proofs/putri_transfer_bukti.jpg',
            'verified_by'       => $sarah,
            'verified_at'       => $now,
            'status'            => 'verified',
            'notes'             => 'Pembayaran via transfer BCA, sudah diverifikasi.',
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        // Jangan lupa update status denda di tabel fines:
        // DB::table('fines')
        //     ->where('id', 'a5b2c3d4-000a-4000-8000-000000000001')
        //     ->update(['status' => 'paid', 'paid_at' => $now, 'paid_amount' => 6000.00]);
        */
    }
}
