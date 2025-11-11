<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->alias([
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum login atau sesi Anda telah berakhir. Silakan login kembali.',
            ], 401);
        });

        $exceptions->render(function (TokenExpiredException $e, \Illuminate\Http\Request $request) {
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Sesi Anda telah berakhir. Silakan login kembali untuk melanjutkan.',
            ], 401);
        });

        $exceptions->render(function (TokenInvalidException $e, \Illuminate\Http\Request $request) {
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid. Silakan login ulang untuk mendapatkan akses.',
            ], 401);
        });

        $exceptions->render(function (TokenBlacklistedException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani untuk route login/register
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Sesi Anda tidak lagi berlaku. Silakan login kembali.',
            ], 401);
        });

        $exceptions->render(function (JWTException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani JWTException untuk route login/register karena mereka tidak memerlukan autentikasi
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register') ||
                          str_contains($request->path(), 'auth/login') ||
                          str_contains($request->path(), 'auth/register');

            if ($isAuthRoute) {
                return null; // Biarkan exception ditangani oleh handler default atau dilempar kembali
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Sesi login tidak ditemukan. Silakan login terlebih dahulu.',
            ], 401);
        });

        $exceptions->render(function (UserNotDefinedException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani untuk route login/register
            $isAuthRoute = $request->is('api/v1/auth/login') ||
                          $request->is('api/v1/auth/register') ||
                          $request->routeIs('auth.login') ||
                          $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Data pengguna tidak ditemukan. Silakan login ulang.',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini.',
            ], 403);
        });

        $exceptions->render(function (ValidationException $e, \Illuminate\Http\Request $request) {
            $errors = $e->errors();

            $isLoginRoute = $request->is('api/v1/auth/login') ||
                           $request->routeIs('auth.login') ||
                           str_contains($request->path(), 'auth/login');

            if ($isLoginRoute && isset($errors['login'])) {
                $loginErrors = $errors['login'];
                $isCredentialError = collect($loginErrors)->first(function ($error) {
                    return stripos($error, 'username') !== false ||
                           stripos($error, 'email') !== false ||
                           stripos($error, 'password') !== false ||
                           stripos($error, 'salah') !== false ||
                           stripos($error, 'kredensial') !== false ||
                           stripos($error, 'credential') !== false;
                });

                if ($isCredentialError) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Username/email atau password salah.',
                        'errors' => $errors,
                    ], 422);
                }
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.',
                'errors' => $errors,
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan.',
                ], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $prev = $e->getPrevious();
                if ($prev instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Data tidak ditemukan.',
                    ], 404);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Endpoint tidak ditemukan.',
                ], 404);
            }
        });
    })
    ->create();
