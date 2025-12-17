<?php

namespace Modules\Auth\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UsersExportMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(public string $filePath, public string $fileName) {}

  public function build()
  {
    return $this->subject("Export Data Pengguna - " . config("app.name"))
      ->markdown("auth::emails.users-export")
      ->attach($this->filePath, [
        "as" => $this->fileName,
        "mime" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      ]);
  }
}
