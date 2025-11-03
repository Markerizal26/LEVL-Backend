<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;

class ChangeEmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $newEmail,
        public readonly string $verifyUrl,
        public readonly int $ttlMinutes,
        public readonly string $code
    ) {}

    public function build(): self
    {
        return $this->subject('Verifikasi Perubahan Email Anda')
            ->view('auth::emails.change-email-verify')
            ->with([
                'user' => $this->user,
                'newEmail' => $this->newEmail,
                'verifyUrl' => $this->verifyUrl,
                'ttlMinutes' => $this->ttlMinutes,
                'code' => $this->code,
            ]);
    }
}


