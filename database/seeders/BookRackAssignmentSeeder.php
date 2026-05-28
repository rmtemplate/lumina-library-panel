<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookRackAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $book = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);
        $rack = fn(int $n) => sprintf('a2b2c3d4-0007-4000-8000-%012d', $n);

        // Rak 1 (Novel): Fiksi + Psikologi books
        // Rak 2 (Teknologi): Teknologi books
        // Rak 3 (Sains): Sains + Sejarah books
        // Rak 4 (Referensi/Basement): Referensi + Agama books
        // Rak 5 (Fiksi Timur): Fiksi books (duplicate di East)
        // Rak 6 (Bisnis): Bisnis books
        $assignments = [
            // Rak Novel Utama (g..1)
            [$book(1),  $rack(1), 'FIK-001'],  // Laskar Pelangi
            [$book(2),  $rack(1), 'FIK-002'],  // Bumi Manusia
            [$book(10), $rack(1), 'FIK-003'],  // Laut Bercerita
            [$book(5),  $rack(1), 'PSI-001'],  // Atomic Habits
            [$book(6),  $rack(1), 'PSI-002'],  // Filosofi Teras
            [$book(15), $rack(1), 'PSI-003'],  // Thinking Fast Slow

            // Rak Teknologi (g..2)
            [$book(4),  $rack(2), 'TEK-001'],  // Clean Code
            [$book(9),  $rack(2), 'TEK-002'],  // Pragmatic Programmer
            [$book(12), $rack(2), 'TEK-003'],  // Design Patterns

            // Rak Sains (g..3)
            [$book(8),  $rack(3), 'SAI-001'],  // Brief History of Time
            [$book(3),  $rack(3), 'SEJ-001'],  // Sapiens
            [$book(11), $rack(3), 'SEJ-002'],  // Sejarah Indonesia

            // Rak Referensi Basement (g..4)
            [$book(14), $rack(4), 'REF-001'],  // KBBI
            [$book(13), $rack(4), 'AGM-001'],  // Kitab Tauhid

            // Rak Fiksi Timur / East (g..5)
            [$book(1),  $rack(5), 'FIK-001E'], // Laskar Pelangi (East copy)
            [$book(2),  $rack(5), 'FIK-002E'], // Bumi Manusia (East copy)

            // Rak Bisnis (g..6)
            [$book(7),  $rack(6), 'BIS-001'],  // Ekonomi Indonesia
        ];

        foreach ($assignments as $a) {
            DB::table('book_rack_assignments')->insert([
                'id'           => Str::uuid()->toString(),
                'book_id'      => $a[0],
                'rack_id'      => $a[1],
                'catalog_code' => $a[2],
                'assigned_at'  => $now->copy()->subMonths(2),
                'is_active'    => true,
                'created_at'   => $now->copy()->subMonths(2),
                'updated_at'   => $now,
            ]);
        }
    }
}
