<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AttachmentSeeder extends Seeder
{
    public function run(): void
    {
        $now   = Carbon::now();
        $budi  = 'c1b2c3d4-0003-4000-8000-000000000001';
        $sarah = 'c1b2c3d4-0003-4000-8000-000000000004';
        $admin = 'c1b2c3d4-0003-4000-8000-000000000006';

        DB::table('attachments')->insert([
            // Avatar Budi
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'user_avatar',
                'entity_id'   => $budi,
                'file_name'   => 'budi_avatar.jpg',
                'file_path'   => 'avatars/users/' . $budi . '/budi_avatar.jpg',
                'file_size'   => 245000,
                'mime_type'   => 'image/jpeg',
                'is_primary'  => true,
                'uploaded_by' => $budi,
                'created_at'  => $now->copy()->subMonths(5),
            ],
            // Avatar Sarah
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'user_avatar',
                'entity_id'   => $sarah,
                'file_name'   => 'sarah_avatar.jpg',
                'file_path'   => 'avatars/users/' . $sarah . '/sarah_avatar.jpg',
                'file_size'   => 310000,
                'mime_type'   => 'image/jpeg',
                'is_primary'  => true,
                'uploaded_by' => $sarah,
                'created_at'  => $now->copy()->subMonths(11),
            ],
            // Cover Laskar Pelangi
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'book_cover',
                'entity_id'   => 'f1b2c3d4-0006-4000-8000-000000000001',
                'file_name'   => 'laskar_pelangi_cover.jpg',
                'file_path'   => 'covers/books/f1b2c3d4-0006-4000-8000-000000000001/laskar_pelangi_cover.jpg',
                'file_size'   => 380000,
                'mime_type'   => 'image/jpeg',
                'is_primary'  => true,
                'uploaded_by' => $admin,
                'created_at'  => $now->copy()->subMonths(3),
            ],
            // Cover Clean Code
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'book_cover',
                'entity_id'   => 'f1b2c3d4-0006-4000-8000-000000000004',
                'file_name'   => 'clean_code_cover.jpg',
                'file_path'   => 'covers/books/f1b2c3d4-0006-4000-8000-000000000004/clean_code_cover.jpg',
                'file_size'   => 420000,
                'mime_type'   => 'image/jpeg',
                'is_primary'  => true,
                'uploaded_by' => $admin,
                'created_at'  => $now->copy()->subMonths(3),
            ],
            // Banner image
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'banner_image',
                'entity_id'   => Str::uuid()->toString(), // linked to banner
                'file_name'   => 'welcome_banner.webp',
                'file_path'   => 'banners/welcome_banner.webp',
                'file_size'   => 520000,
                'mime_type'   => 'image/webp',
                'is_primary'  => true,
                'uploaded_by' => $admin,
                'created_at'  => $now->copy()->subDays(30),
            ],
            // Library photo
            [
                'id'          => Str::uuid()->toString(),
                'entity_type' => 'library_image',
                'entity_id'   => 'd1b2c3d4-0004-4000-8000-000000000001', // Central Library
                'file_name'   => 'central_library_front.jpg',
                'file_path'   => 'libraries/d1b2c3d4-0004-4000-8000-000000000001/central_library_front.jpg',
                'file_size'   => 890000,
                'mime_type'   => 'image/jpeg',
                'is_primary'  => true,
                'uploaded_by' => $admin,
                'created_at'  => $now->copy()->subMonths(6),
            ],
        ]);
    }
}
