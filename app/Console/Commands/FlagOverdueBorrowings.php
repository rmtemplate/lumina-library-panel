<?php

namespace App\Console\Commands;

use App\Services\Api\Borrowing\BorrowingOverdueService;
use Illuminate\Console\Command;

class FlagOverdueBorrowings extends Command
{
    protected $signature = 'lumina:flag-overdue-borrowings {--limit=100}';

    protected $description = 'Automatically flip ACTIVE borrowings past their due_date to WAITING_PAYMENT.';

    public function handle(BorrowingOverdueService $service): int
    {
        $limit = (int) $this->option('limit');

        $flaggedCount = $service->flagDueOverdueBorrowings($limit);

        $this->info("Flagged overdue borrowings: {$flaggedCount}");

        return self::SUCCESS;
    }
}
