<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Support\BrowserLogger;

class ActivityLogObserver
{
  /**
   * Handle the ActivityLog "creating" event.
   */
  public function creating(ActivityLog $activity): void
  {
    // Auto-add browser info to all activity logs
    $deviceInfo = BrowserLogger::getDeviceInfo();

    $activity->ip_address = $deviceInfo["ip_address"];
    $activity->browser = $deviceInfo["browser"];
    $activity->browser_version = $deviceInfo["browser_version"];
    $activity->platform = $deviceInfo["platform"];
    $activity->device = $deviceInfo["device"];
    $activity->device_type = $deviceInfo["device_type"];
  }
}
