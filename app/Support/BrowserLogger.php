<?php

namespace App\Support;

use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

class BrowserLogger
{
  /**
   * Get browser and device information from request
   */
  public static function getDeviceInfo(): array
  {
    try {
      $agent = new Agent();

      return [
        "ip_address" => Request::ip(),
        "browser" => $agent->browser() ?? "Unknown",
        "browser_version" => $agent->version($agent->browser()) ?? null,
        "platform" => $agent->platform() ?? "Unknown",
        "device" => $agent->device() ?? $agent->platform(),
        "device_type" => self::getDeviceType($agent),
      ];
    } catch (\Exception $e) {
      // Fallback if browser detection fails
      return [
        "ip_address" => Request::ip(),
        "browser" => "Unknown",
        "browser_version" => null,
        "platform" => "Unknown",
        "device" => "Unknown",
        "device_type" => "desktop",
      ];
    }
  }

  /**
   * Determine device type
   */
  private static function getDeviceType(Agent $agent): string
  {
    if ($agent->isMobile()) {
      return "mobile";
    }

    if ($agent->isTablet()) {
      return "tablet";
    }

    if ($agent->isDesktop()) {
      return "desktop";
    }

    return "unknown";
  }
}
