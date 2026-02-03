<?php

declare(strict_types=1);


namespace Modules\Auth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Auth\Exports\UsersExport;
use Modules\Mail\Mail\Auth\UsersExportMail;

class ExportUsersToEmailJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(public array $userIds, public string $recipientEmail) {}

  public function handle(): void
  {
    $fileName = "users_export_" . now()->format("Y-m-d_His") . ".xlsx";
    $path = "exports/" . $fileName;

    Excel::store(new UsersExport($this->userIds), $path, "local");

    Mail::to($this->recipientEmail)->send(new \Modules\Mail\Mail\Auth\UsersExportMail($this->recipientEmail, route('profile.exports.download', $fileName), $fileName));

    dispatch(function () use ($path) {
      if (Storage::exists($path)) {
        Storage::delete($path);
      }
    })->delay(now()->addHour());
  }
}
