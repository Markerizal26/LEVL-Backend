<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Models\User;

class UserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $password,
        public readonly string $loginUrl
    ) {}

    public function build(): self
    {
        return $this->subject('Kredensial Akun Anda - Login ke '.config('app.name'))
            ->view('mail::emails.auth.credentials')
            ->with([
                'user' => $this->user,
                'password' => $this->password,
                'loginUrl' => $this->loginUrl,
            ]);
    }
}
