<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsurePermission;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\{
    TokenExpiredException,
    TokenInvalidException,
    TokenBlacklistedException,
    JWTException,
    UserNotDefinedException
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
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
            return response()->json([
                'status' => 'error',
                'message' => 'Anda belum login atau sesi Anda telah berakhir. Silakan login kembali.',
            ], 401);
        });

        $exceptions->render(function (TokenExpiredException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi Anda telah berakhir. Silakan login kembali untuk melanjutkan.',
            ], 401);
        });

        $exceptions->render(function (TokenInvalidException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak valid. Silakan login ulang untuk mendapatkan akses.',
            ], 401);
        });

        $exceptions->render(function (TokenBlacklistedException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi Anda tidak lagi berlaku. Silakan login kembali.',
            ], 401);
        });

        $exceptions->render(function (JWTException $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi login tidak ditemukan. Silakan login terlebih dahulu.',
            ], 401);
        });

        $exceptions->render(function (UserNotDefinedException $e, \Illuminate\Http\Request $request) {
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
            return response()->json([
                'status' => 'error',
                'message' => 'Data yang Anda kirim tidak valid. Periksa kembali isian Anda.',
                'errors' => $e->errors(),
            ], 422);
        });
    })
    ->create();
