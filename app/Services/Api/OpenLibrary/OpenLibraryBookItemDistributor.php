<?php

namespace App\Services\Api\OpenLibrary;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates physical BookItem records for books that have no items yet.
 *
 * Distribution strategy:
 *  - Books in technology/science categories → Jakarta Pusat (tech rack) + Bandung Central
 *  - Books in social/humanities categories  → Jakarta Pusat (social rack) + Jakarta Selatan
 *  - Fiction / children                     → Jakarta Pusat (fiction rack) + Jakarta Barat
 *  - Everything else                        → round-robin across Jakarta branches
 */
class OpenLibraryBookItemDistributor
{
    private const ADMIN = 'c1b2c3d4-0003-4000-8000-000000000006';

    /** category_id → [branch_id, rack_id][] */
    private const DISTRIBUTION = [
        // Teknologi & Komputer / Pemrograman / AI
        'e1b2c3d4-0005-4000-8000-000000000003' => [
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000008'],
            ['d1b2c3d4-0004-4000-8000-000000000001', 'a2b2c3d4-0007-4000-8000-000000000002'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000009' => [ // Pemrograman
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000008'],
            ['d1b2c3d4-0004-4000-8000-000000000001', 'a2b2c3d4-0007-4000-8000-000000000002'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000010' => [ // AI
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000008'],
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000011'],
        ],
        // Sains & Matematika
        'e1b2c3d4-0005-4000-8000-000000000002' => [
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000008'],
            ['d1b2c3d4-0004-4000-8000-000000000001', 'a2b2c3d4-0007-4000-8000-000000000003'],
        ],
        // Fiksi & Sastra / Novel Terjemahan / Sastra Indonesia
        'e1b2c3d4-0005-4000-8000-000000000001' => [
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000007'],
            ['d1b2c3d4-0004-4000-8000-000000000006', 'a2b2c3d4-0007-4000-8000-000000000012'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000022' => [ // Sastra Indonesia
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000007'],
            ['d1b2c3d4-0004-4000-8000-000000000008', 'a2b2c3d4-0007-4000-8000-000000000014'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000027' => [ // Novel Terjemahan
            ['d1b2c3d4-0004-4000-8000-000000000006', 'a2b2c3d4-0007-4000-8000-000000000012'],
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000010'],
        ],
        // Bisnis & Ekonomi / Manajemen / Kewirausahaan
        'e1b2c3d4-0005-4000-8000-000000000005' => [
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000011'],
            ['d1b2c3d4-0004-4000-8000-000000000002', 'a2b2c3d4-0007-4000-8000-000000000006'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000012' => [ // Manajemen
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000011'],
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000009'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000028' => [ // Kewirausahaan
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000011'],
        ],
        // Sejarah & Budaya
        'e1b2c3d4-0005-4000-8000-000000000004' => [
            ['d1b2c3d4-0004-4000-8000-000000000007', 'a2b2c3d4-0007-4000-8000-000000000013'],
            ['d1b2c3d4-0004-4000-8000-000000000001', 'a2b2c3d4-0007-4000-8000-000000000003'],
        ],
        // Psikologi / Self-Help
        'e1b2c3d4-0005-4000-8000-000000000006' => [
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000009'],
            ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000010'],
        ],
        // Agama & Filsafat / Agama Islam
        'e1b2c3d4-0005-4000-8000-000000000007' => [
            ['d1b2c3d4-0004-4000-8000-000000000007', 'a2b2c3d4-0007-4000-8000-000000000013'],
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000009'],
        ],
        'e1b2c3d4-0005-4000-8000-000000000026' => [ // Agama Islam
            ['d1b2c3d4-0004-4000-8000-000000000007', 'a2b2c3d4-0007-4000-8000-000000000013'],
        ],
        // Biografi
        'e1b2c3d4-0005-4000-8000-000000000011' => [
            ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000009'],
            ['d1b2c3d4-0004-4000-8000-000000000008', 'a2b2c3d4-0007-4000-8000-000000000014'],
        ],
    ];

    /** Default fallback: one copy in each Jakarta branch, general rack */
    private const FALLBACK_LOCATIONS = [
        ['d1b2c3d4-0004-4000-8000-000000000004', 'a2b2c3d4-0007-4000-8000-000000000009'],
        ['d1b2c3d4-0004-4000-8000-000000000005', 'a2b2c3d4-0007-4000-8000-000000000010'],
        ['d1b2c3d4-0004-4000-8000-000000000006', 'a2b2c3d4-0007-4000-8000-000000000012'],
        ['d1b2c3d4-0004-4000-8000-000000000007', 'a2b2c3d4-0007-4000-8000-000000000013'],
        ['d1b2c3d4-0004-4000-8000-000000000008', 'a2b2c3d4-0007-4000-8000-000000000014'],
    ];

    /**
     * Create physical items for every book that currently has no items.
     *
     * @return int  Number of item records created
     */
    public function distributeNewBooks(?string $branchCode = null, int $copiesPerBook = 2): int
    {
        $branchId = $branchCode
            ? DB::table('library_branches')->where('code', $branchCode)->value('id')
            : null;

        // Only process books with no existing items
        $books = DB::table('books')
            ->leftJoin('book_items', 'books.id', '=', 'book_items.book_id')
            ->whereNull('book_items.id')
            ->whereNull('books.deleted_at')
            ->select('books.id', 'books.category_id', 'books.title')
            ->get();

        $created = 0;

        foreach ($books as $book) {
            $locations = $this->locationsFor($book->category_id, $branchId, $copiesPerBook);

            // book UUID short fragment — unique per book, avoids title collisions
            $bookRef = strtoupper(substr(str_replace('-', '', $book->id), 0, 8));
            $branchCodes = [];

            foreach ($locations as $i => [$bId, $rId]) {
                if (!isset($branchCodes[$bId])) {
                    $branchCodes[$bId] = strtoupper(
                        preg_replace('/[^a-z0-9]/i', '', DB::table('library_branches')->where('id', $bId)->value('code') ?? 'BRN')
                    );
                }
                $code     = $branchCodes[$bId];
                $suffix   = sprintf('%03d', $i + 1);
                $itemCode = "{$code}-{$bookRef}-{$suffix}";
                $barcode  = 'BC-'.$itemCode;

                // Safety: skip if barcode already exists (re-seeding)
                if (DB::table('book_items')->where('barcode', $barcode)->exists()) {
                    continue;
                }

                DB::table('book_items')->insert([
                    'id'               => (string) Str::uuid(),
                    'book_id'          => $book->id,
                    'branch_id'        => $bId,
                    'rack_id'          => $rId,
                    'item_code'        => mb_substr($itemCode, 0, 80),
                    'barcode'          => mb_substr($barcode, 0, 120),
                    'acquisition_date' => now()->subMonths(rand(1, 12))->toDateString(),
                    'source'           => 'open_library_import',
                    'condition_note'   => null,
                    'status'           => 'available',
                    'is_active'        => true,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                    'deleted_at'       => null,
                    'created_by'       => self::ADMIN,
                    'updated_by'       => self::ADMIN,
                ]);

                $created++;
            }
        }

        return $created;
    }

    /**
     * Pick branch/rack pairs for a given category, limited by $branchId if set.
     *
     * @return array<int, array{0:string,1:string}>
     */
    private function locationsFor(string $categoryId, ?string $branchId, int $copiesPerBook): array
    {
        $candidates = self::DISTRIBUTION[$categoryId] ?? self::FALLBACK_LOCATIONS;

        // If a specific branch is requested, filter to only that branch
        if ($branchId !== null) {
            $filtered = array_filter($candidates, fn ($l) => $l[0] === $branchId);
            if (empty($filtered)) {
                // Use fallback rack in the requested branch
                $fallbackRack = self::FALLBACK_LOCATIONS[0][1];
                $candidates   = [[$branchId, $fallbackRack]];
            } else {
                $candidates = array_values($filtered);
            }
        }

        // Repeat/trim to match copiesPerBook
        $result = [];
        for ($i = 0; $i < $copiesPerBook; $i++) {
            $result[] = $candidates[$i % count($candidates)];
        }

        return $result;
    }
}
