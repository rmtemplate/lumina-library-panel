<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookItemSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';
        $central = 'd1b2c3d4-0004-4000-8000-000000000001';
        $east = 'd1b2c3d4-0004-4000-8000-000000000002';
        $book = fn (int $n): string => sprintf('f1b2c3d4-0006-4000-8000-%012d', $n);
        $rack = fn (int $n): string => sprintf('a2b2c3d4-0007-4000-8000-%012d', $n);

        $items = [
            // Items used by borrowing seeders.
            ['a6b2c3d4-0010-4000-8000-000000000001', $book(3),  $central, $rack(3), 'CEN-HIS-SAP-001', 'BC-CEN-HIS-SAP-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000002', $book(5),  $central, $rack(1), 'CEN-PSI-ATO-001', 'BC-CEN-PSI-ATO-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000003', $book(1),  $central, $rack(1), 'CEN-FIK-LAS-001', 'BC-CEN-FIK-LAS-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000004', $book(4),  $east,    $rack(6), 'EAS-TEK-CLE-001', 'BC-EAS-TEK-CLE-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000005', $book(9),  $east,    $rack(6), 'EAS-TEK-PRA-001', 'BC-EAS-TEK-PRA-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000006', $book(2),  $central, $rack(1), 'CEN-FIK-BUM-001', 'BC-CEN-FIK-BUM-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000007', $book(6),  $central, $rack(1), 'CEN-PSI-FIL-001', 'BC-CEN-PSI-FIL-001', 'borrowed'],
            ['a6b2c3d4-0010-4000-8000-000000000008', $book(10), $central, $rack(1), 'CEN-FIK-LAU-001', 'BC-CEN-FIK-LAU-001', 'borrowed'],

            // Additional available copies for catalog/detail/statistic pages.
            ['a6b2c3d4-0010-4000-8000-000000000009',  $book(1),  $east,    $rack(5), 'EAS-FIK-LAS-001', 'BC-EAS-FIK-LAS-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000010',  $book(2),  $east,    $rack(5), 'EAS-FIK-BUM-001', 'BC-EAS-FIK-BUM-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000011',  $book(4),  $central, $rack(2), 'CEN-TEK-CLE-001', 'BC-CEN-TEK-CLE-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000012',  $book(9),  $central, $rack(2), 'CEN-TEK-PRA-001', 'BC-CEN-TEK-PRA-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000013',  $book(12), $central, $rack(2), 'CEN-TEK-DES-001', 'BC-CEN-TEK-DES-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000014',  $book(8),  $central, $rack(3), 'CEN-SAI-BRI-001', 'BC-CEN-SAI-BRI-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000015',  $book(11), $central, $rack(3), 'CEN-HIS-IND-001', 'BC-CEN-HIS-IND-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000016',  $book(14), $central, $rack(4), 'CEN-REF-KBB-001', 'BC-CEN-REF-KBB-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000017',  $book(13), $central, $rack(4), 'CEN-AGM-TAU-001', 'BC-CEN-AGM-TAU-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000018',  $book(7),  $east,    $rack(6), 'EAS-BIS-EKO-001', 'BC-EAS-BIS-EKO-001', 'available'],
            ['a6b2c3d4-0010-4000-8000-000000000019',  $book(15), $central, $rack(1), 'CEN-PSI-THI-001', 'BC-CEN-PSI-THI-001', 'maintenance'],
            ['a6b2c3d4-0010-4000-8000-000000000020',  $book(5),  $east,    $rack(5), 'EAS-PSI-ATO-001', 'BC-EAS-PSI-ATO-001', 'damaged'],
        ];

        DB::table('book_items')->insert(array_map(function (array $item) use ($now, $admin): array {
            return [
                'id' => $item[0],
                'book_id' => $item[1],
                'branch_id' => $item[2],
                'rack_id' => $item[3],
                'item_code' => $item[4],
                'barcode' => $item[5],
                'acquisition_date' => $now->copy()->subMonths(4)->toDateString(),
                'source' => 'initial_seed',
                'condition_note' => null,
                'status' => $item[6],
                'is_active' => true,
                'created_at' => $now->copy()->subMonths(4),
                'updated_at' => $now,
                'deleted_at' => null,
                'created_by' => $admin,
                'updated_by' => $admin,
            ];
        }, $items));
    }
}
