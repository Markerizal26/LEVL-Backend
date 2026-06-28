<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Support\BrowserLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveActivityLocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 15;

    public function __construct(
        public readonly int $activityLogId,
        public readonly string $ipAddress
    ) {
        $this->onQueue('logging');
    }

    public function handle(): void
    {
        $geo = BrowserLogger::getGeoInfo($this->ipAddress);

        if ($geo['city'] === null && $geo['region'] === null && $geo['country'] === null) {
            return;
        }

        ActivityLog::query()
            ->whereKey($this->activityLogId)
            ->update([
                'city' => $geo['city'],
                'region' => $geo['region'],
                'country' => $geo['country'],
            ]);
    }
}
