<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProfileTypeSeeder::class,
            LibraryBranchSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            UserRoleSeeder::class,
            RackSeeder::class,
            BookCategorySeeder::class,
            StudyProgramSeeder::class,
            BookSeeder::class,
            BookItemSeeder::class,
            OpenLibraryBookSeeder::class,
            BookRackAssignmentSeeder::class,
            BookMoveHistorySeeder::class,
            CartSeeder::class,
            CartItemSeeder::class,
            BorrowingSeeder::class,
            BorrowingItemSeeder::class,
            BorrowingStatusHistorySeeder::class,
            BorrowingQrCodeSeeder::class,
            FineSeeder::class,
            FinePaymentSeeder::class,
            BorrowingRenewalSeeder::class,
            BookReservationSeeder::class,
            BookReviewSeeder::class,
            UserWishlistSeeder::class,
            SearchHistorySeeder::class,
            BookViewSeeder::class,
            TrendingKeywordSeeder::class,
            BannerSeeder::class,
            ActivityLogSeeder::class,
            NotificationSeeder::class,
            NotificationPreferenceSeeder::class,
            AttachmentSeeder::class,
            AppSettingSeeder::class,
        ]);
    }
}
