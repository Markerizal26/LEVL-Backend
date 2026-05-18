<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

final class LoadtestBypass
{
    private const LOCAL_IPS = ['127.0.0.1', '::1'];

    private const HEADER = 'X-Loadtest-Bypass';

    private const HEADER_VALUE = '1';

    public static function isEnabled(?Request $request = null): bool
    {
        $request ??= request();

        if ($request === null) {
            return false;
        }

        if (! in_array($request->ip(), self::LOCAL_IPS, true)) {
            return false;
        }

        return $request->header(self::HEADER) === self::HEADER_VALUE;
    }
}
