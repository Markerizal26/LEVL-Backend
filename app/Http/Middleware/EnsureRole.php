<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak terotorisasi.',
            ], 401);
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden: insufficient role.',
            ], 403);
        }

        return $next($request);
    }
}


