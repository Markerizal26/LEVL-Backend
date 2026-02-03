<?php

declare(strict_types=1);

namespace Modules\Mail\Mail\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UsersExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $email,
        public readonly string $downloadUrl,
        public readonly string $fileName
    ) {}

    public function build(): self
    {
        return $this->subject('File Export Data Pengguna Anda Siap')
            ->markdown('mail::emails.auth.users-export')
            ->with([
                'email' => $this->email,
                'downloadUrl' => $this->downloadUrl,
                'fileName' => $this->fileName,
            ]);
    }
}
