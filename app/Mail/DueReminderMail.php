<?php

namespace App\Mail;

use App\Models\Borrowing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DueReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Borrowing $borrowing,
        public readonly string $reminderType,
        public readonly int $daysLeft,
        public readonly int $overdueDays
    ) {
    }

    public function build(): self
    {
        $subject = match (true) {
            $this->overdueDays > 0 => "Buku Anda Terlambat {$this->overdueDays} Hari — Lumina Library",
            $this->daysLeft === 0 => 'Jatuh Tempo Hari Ini — Lumina Library',
            default => "Pengingat: Buku Jatuh Tempo dalam {$this->daysLeft} Hari — Lumina Library",
        };

        return $this
            ->subject($subject)
            ->view('emails.borrowings.due-reminder')
            ->with([
                'user' => $this->user,
                'borrowing' => $this->borrowing,
                'reminderType' => $this->reminderType,
                'daysLeft' => $this->daysLeft,
                'overdueDays' => $this->overdueDays,
                'logoUrl' => asset('lumina-logo.webp'),
                'year' => now()->year,
                'isOverdue' => $this->overdueDays > 0,
                'items' => $this->borrowing->relationLoaded('items')
                    ? $this->borrowing->items
                    : $this->borrowing->items()->with('book')->get(),
            ]);
    }
}
