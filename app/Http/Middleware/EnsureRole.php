<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\LoadtestBypass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (LoadtestBypass::isEnabled($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthenticated'),
            ], 401);
        }

        if ($user->hasRole('Superadmin')) {
            return $next($request);
        }

        if (! $user->hasAnyRole($roles)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.forbidden'),
            ], 403);
        }

        return $next($request);
    }
}
