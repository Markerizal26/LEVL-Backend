<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\LoadtestBypass;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateLoadtestUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldImpersonate($request)) {
            return $next($request);
        }

        if (! Auth::guard('api')->check()) {
            $user = $this->resolveLoadtestUser();

            if ($user instanceof User) {
                Auth::shouldUse('api');
                Auth::guard('api')->setUser($user);
                $request->setUserResolver(static fn (): User => $user);
            }
        }

        return $next($request);
    }

    private function shouldImpersonate(Request $request): bool
    {
        if (! LoadtestBypass::isEnabled($request)) {
            return false;
        }

        if ($request->hasHeader('Authorization')) {
            return false;
        }

        return true;
    }

    private function resolveLoadtestUser(): ?User
    {
        foreach (['Superadmin', 'Admin', 'Instructor'] as $role) {
            $user = User::query()
                ->active()
                ->whereHas('roles', function ($query) use ($role): void {
                    $query->where('name', $role)->where('guard_name', 'api');
                })
                ->orderBy('id')
                ->first();

            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }
}