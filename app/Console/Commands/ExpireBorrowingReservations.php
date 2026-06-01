<?php

namespace App\Console\Commands;

use App\Services\Api\Borrowing\BorrowingReservationExpiryService;
use Illuminate\Console\Command;

class ExpireBorrowingReservations extends Command
{
    protected $signature = 'lumina:expire-borrowing-reservations {--limit=100}';

    protected $description = 'Expire awaiting pickup borrowings and release reserved book items.';

    public function handle(BorrowingReservationExpiryService $service): int
    {
        $limit = (int) $this->option('limit');

        $expiredCount = $service->expireDueReservations($limit);

        $this->info("Expired borrowing reservations: {$expiredCount}");

        return self::SUCCESS;
    }
}