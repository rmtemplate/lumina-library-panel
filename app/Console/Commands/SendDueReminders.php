<?php

namespace App\Console\Commands;

use App\Services\DueReminderService;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'lumina:send-due-reminders';
    protected $description = 'Process and dispatch due-date reminder jobs for active borrowings';

    public function handle(DueReminderService $reminderService): int
    {
        $this->info('Memproses reminder jatuh tempo...');
        $reminderService->processAll();
        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
