<?php

declare(strict_types=1);

namespace App\Support;

use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Stevebauman\Location\Facades\Location;

class BrowserLogger
{
    private const GEO_CACHE_TTL_SECONDS = 86400;

    private const EMPTY_GEO = [
        'city' => null,
        'region' => null,
        'country' => null,
    ];

    public static function getDeviceInfo(): array
    {
        return self::buildDeviceInfo(true);
    }

    public static function getDeviceInfoFast(): array
    {
        return self::buildDeviceInfo(false);
    }

    public static function getGeoInfo(string $ip): array
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return self::EMPTY_GEO;
        }

        return Cache::remember(
            "browser_logger:geo:{$ip}",
            self::GEO_CACHE_TTL_SECONDS,
            static function () use ($ip): array {
                try {
                    $location = Location::get($ip);

                    if (! $location) {
                        return self::EMPTY_GEO;
                    }

                    return [
                        'city' => $location->cityName,
                        'region' => $location->regionName,
                        'country' => $location->countryName,
                    ];
                } catch (\Throwable) {
                    return self::EMPTY_GEO;
                }
            }
        );
    }

    private static function buildDeviceInfo(bool $includeGeo): array
    {
        $ipAddress = self::getClientIp();

        try {
            $userAgent = self::getUserAgent();

            if (empty($userAgent)) {
                $base = [
                    'ip_address' => $ipAddress,
                    'browser' => 'CLI',
                    'browser_version' => null,
                    'platform' => PHP_OS,
                    'device' => 'Server',
                    'device_type' => 'desktop',
                ];

                return $includeGeo ? array_merge($base, self::getGeoInfo($ipAddress)) : array_merge($base, self::EMPTY_GEO);
            }

            $result = Browser::parse($userAgent);

            $base = [
                'ip_address' => $ipAddress,
                'browser' => $result->browserName() ?: 'Unknown',
                'browser_version' => $result->browserVersion() ?: null,
                'platform' => $result->platformName() ?: 'Unknown',
                'device' => $result->deviceModel() ?: ($result->platformName() ?: 'Unknown'),
                'device_type' => self::getDeviceType($result),
            ];

            return $includeGeo ? array_merge($base, self::getGeoInfo($ipAddress)) : array_merge($base, self::EMPTY_GEO);
        } catch (\Throwable) {
            return array_merge([
                'ip_address' => $ipAddress,
                'browser' => 'Unknown',
                'browser_version' => null,
                'platform' => 'Unknown',
                'device' => 'Unknown',
                'device_type' => 'desktop',
            ], self::EMPTY_GEO);
        }
    }

    private static function getUserAgent(): string
    {
        $request = Request::instance();

        $userAgent = $request->header('User-Agent');
        if (! empty($userAgent)) {
            return $userAgent;
        }

        $alternativeHeaders = [
            'X-Original-User-Agent',
            'X-Device-User-Agent',
            'X-Operamini-Phone-Ua',
            'Device-Stock-Ua',
        ];

        foreach ($alternativeHeaders as $header) {
            $value = $request->header($header);
            if (! empty($value)) {
                return $value;
            }
        }

        $serverVars = ['HTTP_USER_AGENT', 'HTTP_X_ORIGINAL_USER_AGENT'];
        foreach ($serverVars as $var) {
            $value = $request->server($var);
            if (! empty($value)) {
                return $value;
            }
        }

        return '';
    }

    private static function getClientIp(): string
    {
        $request = Request::instance();

        $forwardedHeaders = [
            'X-Forwarded-For',
            'X-Real-IP',
            'CF-Connecting-IP',
            'True-Client-IP',
            'X-Client-IP',
        ];

        foreach ($forwardedHeaders as $header) {
            $value = $request->header($header);
            if (! empty($value)) {
                $ips = array_map('trim', explode(',', $value));
                $clientIp = $ips[0];
                if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                    return $clientIp;
                }
            }
        }

        return $request->ip() ?? '127.0.0.1';
    }

    private static function getDeviceType($result): string
    {
        if ($result->isMobile()) {
            return 'mobile';
        }

        if ($result->isTablet()) {
            return 'tablet';
        }

        if ($result->isDesktop()) {
            return 'desktop';
        }

        return 'unknown';
    }
}
