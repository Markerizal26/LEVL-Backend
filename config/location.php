<?php

return [

    'driver' => Stevebauman\Location\Drivers\IpApi::class,

    'fallbacks' => [
        Stevebauman\Location\Drivers\IpInfo::class,
    ],

    'position' => Stevebauman\Location\Position::class,

    'http' => [
        'timeout' => 1,
        'connect_timeout' => 1,
    ],

    'testing' => [
        'ip' => '66.102.0.0',
        'enabled' => env('LOCATION_TESTING', true),
    ],

    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),

        'web' => [
            'enabled' => false,
            'user_id' => env('MAXMIND_USER_ID'),
            'locales' => ['en'],
            'options' => ['host' => 'geoip.maxmind.com'],
        ],

        'local' => [
            'type' => 'city',
            'path' => database_path('maxmind/GeoLite2-City.mmdb'),
            'url' => sprintf('https://download.maxmind.com/app/geoip_download_by_token?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', env('MAXMIND_LICENSE_KEY')),
        ],
    ],

    'ip_api' => [
        'token' => env('IP_API_TOKEN'),
    ],

    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),
    ],

    'ipdata' => [
        'token' => env('IPDATA_TOKEN'),
    ],

    'ip2locationio' => [
        'token' => env('IP2LOCATIONIO_TOKEN'),
    ],

    'kloudend' => [

        'token' => env('KLOUDEND_TOKEN'),

    ],

];
