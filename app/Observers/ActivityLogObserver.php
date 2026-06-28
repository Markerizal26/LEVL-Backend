<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\ResolveActivityLocationJob;
use App\Models\ActivityLog;
use App\Support\BrowserLogger;

class ActivityLogObserver
{
    public function creating(ActivityLog $activity): void
    {
        $deviceInfo = BrowserLogger::getDeviceInfoFast();

        $activity->ip_address = $deviceInfo['ip_address'];
        $activity->browser = $deviceInfo['browser'];
        $activity->browser_version = $deviceInfo['browser_version'];
        $activity->platform = $deviceInfo['platform'];
        $activity->device = $deviceInfo['device'];
        $activity->device_type = $deviceInfo['device_type'];
    }

    public function created(ActivityLog $activity): void
    {
        if (! $activity->ip_address) {
            return;
        }

        ResolveActivityLocationJob::dispatch($activity->id, (string) $activity->ip_address);
    }
}
