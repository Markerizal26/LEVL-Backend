<?php

declare(strict_types=1);

namespace Modules\Mail\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public function send(string $mailClass, mixed $mailable, string $recipient): void
    {
        if (is_string($mailable)) {
            $mailable = app($mailClass, ['recipient' => $recipient]);
        }

        Mail::to($recipient)->send($mailable);
    }

    public function queue(string $mailClass, mixed $mailable, string $recipient): void
    {
        if (is_string($mailable)) {
            $mailable = app($mailClass, ['recipient' => $recipient]);
        }

        Mail::to($recipient)->queue($mailable);
    }

    public function sendMultiple(string $mailClass, mixed $mailable, array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $this->send($mailClass, $mailable, $recipient);
        }
    }

    public function queueMultiple(string $mailClass, mixed $mailable, array $recipients): void
    {
        foreach ($recipients as $recipient) {
            $this->queue($mailClass, $mailable, $recipient);
        }
    }
}
