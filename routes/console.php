<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('lumina:expire-borrowing-reservations --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

// Run once a day at 08:00 WIB to dispatch due-date reminder jobs.
Schedule::command('lumina:send-due-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();