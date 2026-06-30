<?php

namespace Database\Seeders;

use App\Services\Api\OpenLibrary\OpenLibraryBookItemDistributor;
use App\Services\Api\OpenLibrary\OpenLibraryImportService;
use Illuminate\Database\Seeder;

/**
 * Seeds books table with real metadata from the Open Library Search API.
 *
 * Run independently:   php artisan db:seed --class=OpenLibraryBookSeeder
 * Bulk import:         php artisan books:import-openlibrary --limit=100
 *
 * Limit is kept low (15/subject) so db:seed finishes in a few minutes.
 * For larger imports, use the artisan command.
 */
class OpenLibraryBookSeeder extends Seeder
{
    private const LIMIT_PER_SUBJECT = 15;
    private const COPIES_PER_BOOK   = 2;
    private const ADMIN_ID          = 'c1b2c3d4-0003-4000-8000-000000000006';

    private const SUBJECTS = [
        'programming',
        'computer science',
        'artificial intelligence',
        'fiction',
        'history',
        'psychology',
        'business',
        'philosophy',
        'mathematics',
        'biology',
        'management',
        'biography',
    ];

    public function run(): void
    {
        $map         = OpenLibraryImportService::subjectCategoryMap();
        $totalImport = 0;
        $totalSkip   = 0;
        $totalErrors = [];

        foreach (self::SUBJECTS as $subject) {
            $categoryId = $map[$subject] ?? 'e1b2c3d4-0005-4000-8000-000000000003';

            $service = new OpenLibraryImportService(
                adminId:    self::ADMIN_ID,
                categoryId: $categoryId,
            );

            $this->command?->getOutput()?->write("  <comment>[OpenLibrary]</comment> {$subject}… ");

            // No language filter — OL tagging is inconsistent; filtering by 'eng' drops
            // many valid results that simply have no language tag.
            $stats = $service->importBySubject(
                subject: $subject,
                limit:   self::LIMIT_PER_SUBJECT,
            );

            $errCount = count($stats['errors']);
            $this->command?->getOutput()?->writeln(sprintf(
                '<info>imported=%d</info>  skipped=%d  <comment>errors=%d</comment>',
                $stats['imported'],
                $stats['skipped'],
                $errCount,
            ));

            foreach ($stats['errors'] as $detail) {
                $this->command?->getOutput()?->writeln("    <fg=red>✗</> {$detail}");
            }

            $totalImport += $stats['imported'];
            $totalSkip   += $stats['skipped'];
            $totalErrors  = array_merge($totalErrors, $stats['errors']);
        }

        $this->command?->getOutput()?->writeln(sprintf(
            '  <info>[OpenLibrary]</info> Total — imported=%d  skipped=%d  errors=%d',
            $totalImport,
            $totalSkip,
            count($totalErrors),
        ));

        if ($totalImport === 0) {
            $this->command?->getOutput()?->writeln(
                '  <comment>[OpenLibrary]</comment> No new books imported; skipping item distribution.'
            );
            return;
        }

        // Distribute physical copies for every newly-imported book
        $this->command?->getOutput()?->write('  <comment>[OpenLibrary]</comment> Distributing physical items… ');

        $distributor = new OpenLibraryBookItemDistributor();
        $created     = $distributor->distributeNewBooks(copiesPerBook: self::COPIES_PER_BOOK);

        $this->command?->getOutput()?->writeln("<info>{$created} items created.</info>");
    }
}
