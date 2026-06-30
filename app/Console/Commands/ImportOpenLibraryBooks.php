<?php

namespace App\Console\Commands;

use App\Services\Api\OpenLibrary\OpenLibraryBookItemDistributor;
use App\Services\Api\OpenLibrary\OpenLibraryImportService;
use Illuminate\Console\Command;

/**
 * php artisan books:import-openlibrary
 *
 * Imports books from the Open Library Search API and optionally distributes
 * physical copies across Jakarta library branches.
 *
 * Examples:
 *   php artisan books:import-openlibrary --subject=programming --limit=50
 *   php artisan books:import-openlibrary --subject=fiction --limit=100 --page=3
 *   php artisan books:import-openlibrary --subject=history --language=eng --copies=3
 *   php artisan books:import-openlibrary --subject=programming --branch=JKP --no-items
 */
class ImportOpenLibraryBooks extends Command
{
    protected $signature = 'books:import-openlibrary
                            {--subject=* : Subject tag(s) to search — repeat for multiple: --subject=fiction --subject=history}
                            {--limit=50  : Maximum books per subject}
                            {--page=1    : Start page (1-based, useful for resuming a large import)}
                            {--language= : ISO 639-2 language filter (e.g. eng, ind) — empty = all languages}
                            {--branch=   : Branch code to assign physical copies (empty = all Jakarta branches)}
                            {--copies=2  : Physical copies per book (1–8)}
                            {--no-items  : Skip physical book item generation}';

    protected $description = 'Import books from the Open Library API and create physical copies';

    private const ADMIN_ID = 'c1b2c3d4-0003-4000-8000-000000000006';

    private const DEFAULT_SUBJECTS = [
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

    public function handle(): int
    {
        $subjects   = $this->option('subject') ?: self::DEFAULT_SUBJECTS;
        $limit      = max(1, (int) $this->option('limit'));
        $startPage  = max(1, (int) $this->option('page'));
        $language   = (string) ($this->option('language') ?? '');
        $copies     = max(1, min(8, (int) $this->option('copies')));
        $noItems    = (bool) $this->option('no-items');
        $branchCode = (string) ($this->option('branch') ?? '');

        $this->line('');
        $this->info('┌─ Open Library Book Importer ─────────────────────────┐');
        $this->line("│  Subjects  : " . implode(', ', $subjects));
        $this->line("│  Limit     : {$limit} per subject");
        $this->line("│  Start page: {$startPage}");
        $this->line("│  Language  : " . ($language ?: 'all'));
        $this->line("│  Copies    : {$copies}");
        $this->info('└──────────────────────────────────────────────────────┘');
        $this->line('');

        $categoryMap   = OpenLibraryImportService::subjectCategoryMap();
        $totalImported = 0;
        $totalSkipped  = 0;
        $totalErrors   = 0;

        foreach ($subjects as $subject) {
            $categoryId = $categoryMap[$subject] ?? 'e1b2c3d4-0005-4000-8000-000000000003';

            $service = new OpenLibraryImportService(
                adminId:    self::ADMIN_ID,
                categoryId: $categoryId,
            );

            $this->line("<fg=cyan>▶ {$subject}</>");
            $bar = $this->output->createProgressBar($limit);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%%  imported=%imported% skipped=%skipped%');
            $bar->setMessage('0', 'imported');
            $bar->setMessage('0', 'skipped');
            $bar->start();

            $stats = $service->importBySubject(
                subject:    $subject,
                limit:      $limit,
                language:   $language,
                startPage:  $startPage,
                onProgress: function (array $s) use ($bar) {
                    $bar->setProgress(min($s['imported'] + $s['skipped'], $bar->getMaxSteps()));
                    $bar->setMessage((string) $s['imported'], 'imported');
                    $bar->setMessage((string) $s['skipped'],  'skipped');
                },
            );

            $bar->finish();
            $this->newLine(2);

            // Summary line
            $errCount = count($stats['errors']);
            $this->line(sprintf(
                '  <info>✓ Imported:</info> %d  <comment>Skipped:</comment> %d  %s',
                $stats['imported'],
                $stats['skipped'],
                $errCount > 0 ? "<error> Failed: {$errCount} </error>" : '<fg=green>✓ No errors</>',
            ));

            // Print each error detail
            foreach ($stats['errors'] as $errDetail) {
                $this->line("    <fg=red>✗</> {$errDetail}");
            }

            $totalImported += $stats['imported'];
            $totalSkipped  += $stats['skipped'];
            $totalErrors   += $errCount;

            $this->line('');
            usleep(500_000); // 0.5 s pause between subjects
        }

        // Grand total
        $this->info('┌─ Summary ─────────────────────────────────────────────┐');
        $this->line("│  Imported : {$totalImported}");
        $this->line("│  Skipped  : {$totalSkipped}  (duplicates + no title)");
        $this->line("│  Errors   : {$totalErrors}");
        $this->info('└───────────────────────────────────────────────────────┘');

        if ($noItems || $totalImported === 0) {
            return self::SUCCESS;
        }

        // Create physical copies for all newly-imported books
        $this->line('');
        $this->info('Distributing physical book items across branches…');

        $distributor = new OpenLibraryBookItemDistributor();
        $itemCount   = $distributor->distributeNewBooks(
            branchCode:   $branchCode ?: null,
            copiesPerBook: $copies,
        );

        $this->info("Created {$itemCount} physical book items.");

        return self::SUCCESS;
    }
}
