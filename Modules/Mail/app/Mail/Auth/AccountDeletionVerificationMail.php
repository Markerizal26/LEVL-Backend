<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountDeletionVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $confirmUrl,
        public readonly int $ttlMinutes
    ) {}

    public function build(): self
    {
        return $this->subject('Konfirmasi Penghapusan Akun')
            ->view('mail::emails.auth.account-deletion-verify')
            ->with([
                'email' => $this->email,
                'confirmUrl' => $this->confirmUrl,
                'ttlMinutes' => $this->ttlMinutes,
            ]);
    }
}
