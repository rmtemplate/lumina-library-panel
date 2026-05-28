<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 1 perpanjangan peminjaman (Rina memperpanjang Laskar Pelangi).
 */
class BorrowingRenewalSeeder extends Seeder
{
    public function run(): void
    {
        $now  = Carbon::now();
        $rina = 'c1b2c3d4-0003-4000-8000-000000000002';

        DB::table('borrowing_renewals')->insert([
            'id'                => Str::uuid()->toString(),
            'borrowing_item_id' => 'a4b2c3d4-0009-4000-8000-000000000003', // Laskar Pelangi
            'previous_due_date' => $now->copy()->subDays(3)->toDateString(), // seharusnya due 3 hari lalu
            'new_due_date'      => $now->copy()->addDays(4)->toDateString(), // diperpanjang 7 hari
            'renewal_count'     => 1,
            'renewed_at'        => $now->copy()->subDays(3), // perpanjangan dilakukan 3 hari lalu
            'renewed_by'        => $rina,
            'approved_by'       => null, // auto-approved (renewal pertama)
            'notes'             => 'Perpanjangan otomatis pertama. Sisa kesempatan perpanjangan: 1.',
        ]);
    }
}
