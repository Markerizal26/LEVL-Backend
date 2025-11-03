<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $resetUrl,
        public readonly int $ttlMinutes,
        public readonly string $code
    ) {}

    public function build(): self
    {
        return $this->subject('Reset Kata Sandi Akun Anda')
            ->view('auth::emails.reset')
            ->with([
                'user' => $this->user,
                'resetUrl' => $this->resetUrl,
                'ttlMinutes' => $this->ttlMinutes,
                'code' => $this->code,
            ]);
    }
}


