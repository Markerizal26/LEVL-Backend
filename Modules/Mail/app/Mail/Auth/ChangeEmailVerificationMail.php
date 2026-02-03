<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $newEmail,
        public readonly string $verifyUrl,
        public readonly int $ttlMinutes
    ) {}

    public function build(): self
    {
        return $this->subject('Verifikasi Perubahan Email Anda')
            ->view('mail::emails.auth.change-email-verify')
            ->with([
                'newEmail' => $this->newEmail,
                'verifyUrl' => $this->verifyUrl,
                'ttlMinutes' => $this->ttlMinutes,
            ]);
    }
}
