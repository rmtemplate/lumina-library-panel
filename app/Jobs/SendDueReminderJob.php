<?php

namespace App\Jobs;

use App\Mail\DueReminderMail;
use App\Models\Borrowing;
use App\Models\BorrowingReminderLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendDueReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        public readonly string $borrowingId,
        public readonly string $userId,
        public readonly string $reminderType,
        public readonly int $daysLeft,
        public readonly int $overdueDays
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        $borrowing = Borrowing::with(['items.book', 'user'])->find($this->borrowingId);
        $user = User::find($this->userId);

        if (! $borrowing || ! $user) {
            return;
        }

        // Guard: if borrowing is no longer active, skip.
        if (! in_array($borrowing->status, ['active', 'overdue'], true)) {
            return;
        }

        $this->sendInApp($notificationService, $user, $borrowing);
        $this->sendEmail($user, $borrowing);
    }

    private function sendInApp(NotificationService $notificationService, User $user, Borrowing $borrowing): void
    {
        if (BorrowingReminderLog::alreadySent($this->borrowingId, $this->reminderType, BorrowingReminderLog::CHANNEL_IN_APP)) {
            return;
        }

        try {
            if ($this->overdueDays > 0) {
                $notificationService->notifyOverdueAlert($user, $borrowing->id, $this->overdueDays);
            } else {
                $notificationService->notifyDueReminder($user, $borrowing->id, $this->daysLeft, (string) $borrowing->due_date?->format('d M Y'));
            }

            BorrowingReminderLog::create([
                'borrowing_id' => $this->borrowingId,
                'user_id' => $this->userId,
                'reminder_type' => $this->reminderType,
                'channel' => BorrowingReminderLog::CHANNEL_IN_APP,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Gagal kirim in-app reminder: {$this->reminderType}", [
                'borrowing_id' => $this->borrowingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendEmail(User $user, Borrowing $borrowing): void
    {
        if (BorrowingReminderLog::alreadySent($this->borrowingId, $this->reminderType, BorrowingReminderLog::CHANNEL_EMAIL)) {
            return;
        }

        // Respect user's email preference.
        $pref = $user->notificationPreference;
        if ($pref && ! $pref->email_enabled) {
            return;
        }

        try {
            Mail::to($user->email)->send(new DueReminderMail(
                user: $user,
                borrowing: $borrowing,
                reminderType: $this->reminderType,
                daysLeft: $this->daysLeft,
                overdueDays: $this->overdueDays,
            ));

            BorrowingReminderLog::create([
                'borrowing_id' => $this->borrowingId,
                'user_id' => $this->userId,
                'reminder_type' => $this->reminderType,
                'channel' => BorrowingReminderLog::CHANNEL_EMAIL,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error("Gagal kirim email reminder: {$this->reminderType}", [
                'borrowing_id' => $this->borrowingId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendDueReminderJob gagal.', [
            'borrowing_id' => $this->borrowingId,
            'reminder_type' => $this->reminderType,
            'error' => $exception->getMessage(),
        ]);
    }
}
