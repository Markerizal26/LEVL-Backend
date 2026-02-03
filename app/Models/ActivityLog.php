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
   /**
   * Scope query for created_at between dates
   */
   /**
   * Scope query for created_at between dates.
   * Spatie Query Builder passes values as spread arguments: $query, ...$values
   */
  public function scopeCreatedAtBetween($query, ...$dates)
  {
      // If passed as a single string "start,end" via API
      if (count($dates) === 1 && is_string($dates[0]) && str_contains($dates[0], ',')) {
          $dates = explode(',', $dates[0]);
      }
      // If passed as array or multiple args
      if (count($dates) === 1 && is_array($dates[0])) {
          $dates = $dates[0];
      }

      if (count($dates) >= 2) {
            return $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($dates[0])->startOfDay(), 
                \Carbon\Carbon::parse($dates[1])->endOfDay()
            ]);
      }

      return $query;
  }
}
