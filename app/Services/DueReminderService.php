<?php

namespace App\Services;

use App\Jobs\SendDueReminderJob;
use App\Models\Borrowing;
use App\Models\BorrowingReminderLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Determines which active borrowings need a reminder and dispatches jobs.
 *
 * Reminder schedule (configurable via constants):
 *   Before due: H-3, H-1
 *   Due today:  H-0
 *   Overdue:    day 1, day 3, day 7
 *
 * Adding a new reminder type requires:
 *   1. Add a constant to BorrowingReminderLog.
 *   2. Add an entry to SCHEDULE below.
 *   3. No migration or schema change needed.
 */
class DueReminderService
{
    /**
     * [reminderType => daysFromDue]
     *   Negative value = days before due date (before_due)
     *   Positive value = overdue days (after_due)
     *   Zero = due today
     */
    private const SCHEDULE = [
        BorrowingReminderLog::DUE_MINUS_3 => -3,
        BorrowingReminderLog::DUE_MINUS_1 => -1,
        BorrowingReminderLog::DUE_TODAY => 0,
        BorrowingReminderLog::OVERDUE_DAY_1 => 1,
        BorrowingReminderLog::OVERDUE_DAY_3 => 3,
        BorrowingReminderLog::OVERDUE_DAY_7 => 7,
    ];

    public function processAll(): void
    {
        $today = now()->startOfDay();
        $dispatched = 0;

        foreach (self::SCHEDULE as $reminderType => $dayOffset) {
            $targetDate = $today->copy()->addDays(-$dayOffset); // due_date == targetDate triggers this reminder

            $borrowings = Borrowing::query()
                ->with('user.notificationPreference')
                ->where('status', 'active')
                ->whereDate('due_date', $targetDate->toDateString())
                ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('is_verified', true))
                ->get();

            foreach ($borrowings as $borrowing) {
                if (! $borrowing->user) {
                    continue;
                }

                // Skip if BOTH channels already sent for this type.
                $inAppSent = BorrowingReminderLog::alreadySent($borrowing->id, $reminderType, BorrowingReminderLog::CHANNEL_IN_APP);
                $emailSent = BorrowingReminderLog::alreadySent($borrowing->id, $reminderType, BorrowingReminderLog::CHANNEL_EMAIL);

                if ($inAppSent && $emailSent) {
                    continue;
                }

                $daysLeft = max(0, $dayOffset < 0 ? abs($dayOffset) : 0);
                $overdueDays = max(0, $dayOffset > 0 ? $dayOffset : 0);

                SendDueReminderJob::dispatch(
                    borrowingId: $borrowing->id,
                    userId: $borrowing->user_id,
                    reminderType: $reminderType,
                    daysLeft: $daysLeft,
                    overdueDays: $overdueDays
                );

                $dispatched++;
            }
        }

        Log::info("DueReminderService: {$dispatched} reminder job(s) dispatched.");
    }
}
