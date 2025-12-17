<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class ActivityLog extends SpatieActivity
{
  protected $appends = ["device_info"];

  /**
   * Get formatted device info
   */
  public function getDeviceInfoAttribute(): string
  {
    $parts = array_filter([$this->browser, $this->browser_version, $this->platform]);

    return $parts ? implode(" / ", $parts) : "-";
  }

  /**
   * Get icon name for device type
   */
  public function getDeviceIconAttribute(): string
  {
    return match ($this->device_type) {
      "mobile" => "smartphone",
      "tablet" => "tablet",
      "desktop" => "monitor",
      default => "help-circle",
    };
  }
}
