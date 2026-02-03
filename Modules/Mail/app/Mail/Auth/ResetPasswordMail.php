<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $resetUrl,
        public readonly int $ttlMinutes
    ) {}

    public function build(): self
    {
        return $this->subject('Reset Password Akun Anda')
            ->view('mail::emails.auth.reset')
            ->with([
                'email' => $this->email,
                'resetUrl' => $this->resetUrl,
                'ttlMinutes' => $this->ttlMinutes,
            ]);
    }
}
