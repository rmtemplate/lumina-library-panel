<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $programs = [
            ['id' => 'f1b2c3d4-0006-4000-8000-000000000001', 'code' => 'informatika', 'name' => 'Informatika', 'category_ids' => ['e1b2c3d4-0005-4000-8000-000000000003', 'e1b2c3d4-0005-4000-8000-000000000002']],
            ['id' => 'f1b2c3d4-0006-4000-8000-000000000002', 'code' => 'akuntansi', 'name' => 'Akuntansi', 'category_ids' => ['e1b2c3d4-0005-4000-8000-000000000005', 'e1b2c3d4-0005-4000-8000-000000000008']],
            ['id' => 'f1b2c3d4-0006-4000-8000-000000000003', 'code' => 'desain', 'name' => 'Design', 'category_ids' => ['e1b2c3d4-0005-4000-8000-000000000001', 'e1b2c3d4-0005-4000-8000-000000000004']],
        ];
        foreach ($programs as $program) {
            DB::table('study_programs')->insert([
                'id' => $program['id'], 'code' => $program['code'], 'name' => $program['name'],
                'description' => 'Program rekomendasi awal untuk '.$program['name'], 'keywords' => json_encode([$program['code']]),
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($program['category_ids'] as $categoryId) {
                DB::table('study_program_book_categories')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(), 'study_program_id' => $program['id'], 'book_category_id' => $categoryId,
                    'weight' => 1.00, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}
