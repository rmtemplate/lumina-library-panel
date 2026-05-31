<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $verificationUrl
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Verifikasi Email Anda - Lumina Library')
            ->view('emails.auth.verify-email')
            ->with([
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
                'logoUrl' => asset('lumina-logo.webp'),
                'year' => now()->year,
            ]);
    }
}