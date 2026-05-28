<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Distribusi stok buku per perpustakaan.
 *
 * Central Library: 15 buku (semua)
 * East Campus: 10 buku
 * Digital Corner: 5 buku (teknologi & referensi saja)
 *
 * Beberapa buku punya available_stock = 0 (untuk test reservasi)
 */
class BookStockSeeder extends Seeder
{
    public function run(): void
    {
        $now     = Carbon::now();
        $admin   = 'c1b2c3d4-0003-4000-8000-000000000006';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east    = 'd1b2c3d4-0004-4000-8000-000000000002';
        $digital = 'd1b2c3d4-0004-4000-8000-000000000003';

        $book = fn(int $n) => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);

        $stocks = [
            // ── Central Library: ALL 15 books ──
            ['book' => $book(1),  'branch' => $central, 'shelf' => 'Rak A-01', 'total' => 5,  'avail' => 4],  // Laskar Pelangi (-1 dipinjam Rina)
            ['book' => $book(2),  'branch' => $central, 'shelf' => 'Rak A-01', 'total' => 4,  'avail' => 4],  // Bumi Manusia (sudah returned)
            ['book' => $book(3),  'branch' => $central, 'shelf' => 'Rak C-02', 'total' => 3,  'avail' => 0],  // Sapiens → HABIS (di cart Budi pending)
            ['book' => $book(4),  'branch' => $central, 'shelf' => 'Rak B-01', 'total' => 5,  'avail' => 4],  // Clean Code (-1 dipinjam Dimas via East)
            ['book' => $book(5),  'branch' => $central, 'shelf' => 'Rak A-03', 'total' => 6,  'avail' => 5],  // Atomic Habits
            ['book' => $book(6),  'branch' => $central, 'shelf' => 'Rak A-03', 'total' => 4,  'avail' => 3],  // Filosofi Teras (-1 overdue Putri)
            ['book' => $book(7),  'branch' => $central, 'shelf' => 'Rak D-01', 'total' => 3,  'avail' => 3],  // Ekonomi Indonesia
            ['book' => $book(8),  'branch' => $central, 'shelf' => 'Rak B-02', 'total' => 3,  'avail' => 3],  // Brief History of Time
            ['book' => $book(9),  'branch' => $central, 'shelf' => 'Rak B-01', 'total' => 4,  'avail' => 3],  // Pragmatic Programmer (-1 Dimas)
            ['book' => $book(10), 'branch' => $central, 'shelf' => 'Rak A-02', 'total' => 3,  'avail' => 2],  // Laut Bercerita (-1 overdue Putri)
            ['book' => $book(11), 'branch' => $central, 'shelf' => 'Rak C-01', 'total' => 3,  'avail' => 3],  // Sejarah Indonesia
            ['book' => $book(12), 'branch' => $central, 'shelf' => 'Rak B-01', 'total' => 4,  'avail' => 4],  // Design Patterns
            ['book' => $book(13), 'branch' => $central, 'shelf' => 'Rak E-01', 'total' => 5,  'avail' => 5],  // Kitab Tauhid
            ['book' => $book(14), 'branch' => $central, 'shelf' => 'Rak E-02', 'total' => 10, 'avail' => 10], // KBBI
            ['book' => $book(15), 'branch' => $central, 'shelf' => 'Rak A-03', 'total' => 3,  'avail' => 3],  // Thinking Fast Slow

            // ── East Campus: 10 books ──
            ['book' => $book(1),  'branch' => $east, 'shelf' => 'Rak F-01', 'total' => 3, 'avail' => 3],
            ['book' => $book(2),  'branch' => $east, 'shelf' => 'Rak F-01', 'total' => 2, 'avail' => 2],
            ['book' => $book(4),  'branch' => $east, 'shelf' => 'Rak G-01', 'total' => 3, 'avail' => 2],  // -1 dipinjam Dimas
            ['book' => $book(5),  'branch' => $east, 'shelf' => 'Rak F-02', 'total' => 2, 'avail' => 2],
            ['book' => $book(7),  'branch' => $east, 'shelf' => 'Rak H-01', 'total' => 2, 'avail' => 2],
            ['book' => $book(8),  'branch' => $east, 'shelf' => 'Rak G-02', 'total' => 2, 'avail' => 2],
            ['book' => $book(9),  'branch' => $east, 'shelf' => 'Rak G-01', 'total' => 2, 'avail' => 1],  // -1 dipinjam Dimas
            ['book' => $book(11), 'branch' => $east, 'shelf' => 'Rak H-02', 'total' => 2, 'avail' => 2],
            ['book' => $book(12), 'branch' => $east, 'shelf' => 'Rak G-01', 'total' => 2, 'avail' => 2],
            ['book' => $book(15), 'branch' => $east, 'shelf' => 'Rak F-02', 'total' => 2, 'avail' => 2],

            // ── Digital Corner: 5 books (teknologi & referensi only) ──
            ['book' => $book(4),  'branch' => $digital, 'shelf' => 'Rak D-01', 'total' => 2, 'avail' => 2],  // Clean Code
            ['book' => $book(9),  'branch' => $digital, 'shelf' => 'Rak D-01', 'total' => 2, 'avail' => 2],  // Pragmatic
            ['book' => $book(12), 'branch' => $digital, 'shelf' => 'Rak D-02', 'total' => 2, 'avail' => 2],  // Design Patterns
            ['book' => $book(14), 'branch' => $digital, 'shelf' => 'Rak D-03', 'total' => 3, 'avail' => 3],  // KBBI
            ['book' => $book(13), 'branch' => $digital, 'shelf' => 'Rak D-03', 'total' => 2, 'avail' => 2],  // Kitab Tauhid
        ];

        foreach ($stocks as $s) {
            DB::table('book_stocks')->insert([
                'id'                => Str::uuid()->toString(),
                'book_id'           => $s['book'],
                'library_branch_id' => $s['branch'],
                'shelf_location'    => $s['shelf'],
                'total_stock'       => $s['total'],
                'available_stock'   => $s['avail'],
                'created_at'        => $now,
                'updated_at'        => $now,
                'deleted_at'        => null,
                'created_by'        => $admin,
                'updated_by'        => $admin,
            ]);
        }
    }
}
