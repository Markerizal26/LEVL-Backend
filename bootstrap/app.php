<?php

use App\Exceptions\BusinessException;
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
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
        ]);

        // Trust all proxies (for AWS/load balancers) - allows proper IP and header detection
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        // Set locale before processing API requests
        $middleware->api(prepend: [\App\Http\Middleware\SetLocale::class]);

        // Apply rate limiting to all API routes
        $middleware->api(prepend: [\Illuminate\Routing\Middleware\ThrottleRequests::class.':api']);

        // Enable CORS for API routes
        $middleware->api(prepend: [\Illuminate\Http\Middleware\HandleCors::class]);

        $middleware->append(\App\Http\Middleware\LogApiAction::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle specific BusinessException subclasses first
        $exceptions->render(function (\App\Exceptions\ResourceNotFoundException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.not_found');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => [],
                ],
                404,
            );
        });

        $exceptions->render(function (\App\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.unauthorized');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => [],
                ],
                401,
            );
        });

        $exceptions->render(function (\App\Exceptions\ForbiddenException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.forbidden');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => [],
                ],
                403,
            );
        });

        $exceptions->render(function (\App\Exceptions\DuplicateResourceException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.duplicate_entry');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => $e->getErrors(),
                ],
                409,
            );
        });

        $exceptions->render(function (\Spatie\QueryBuilder\Exceptions\InvalidFilterQuery $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
                'errors' => null,
            ], 400);
        });

        $exceptions->render(function (\Spatie\QueryBuilder\Exceptions\InvalidSortQuery $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
                'errors' => null,
            ], 400);
        });

        // Handle generic BusinessException for business rule violations
        $exceptions->render(function (BusinessException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.error');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'meta' => null,
                    'errors' => $e->getErrors(),
                ],
                $e->getCode(),
            );
        });

        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request,
        ) {
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.unauthenticated'),
                ],
                401,
            );
        });

        $exceptions->render(function (TokenExpiredException $e, \Illuminate\Http\Request $request) {
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.session_expired'),
                ],
                401,
            );
        });

        $exceptions->render(function (TokenInvalidException $e, \Illuminate\Http\Request $request) {
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.session_invalid'),
                ],
                401,
            );
        });

        $exceptions->render(function (TokenBlacklistedException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani untuk route login/register
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.session_blacklisted'),
                ],
                401,
            );
        });

        $exceptions->render(function (JWTException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani JWTException untuk route login/register karena mereka tidak memerlukan autentikasi
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register') ||
              str_contains($request->path(), 'auth/login') ||
              str_contains($request->path(), 'auth/register');

            if ($isAuthRoute) {
                return null; // Biarkan exception ditangani oleh handler default atau dilempar kembali
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.session_not_found'),
                ],
                401,
            );
        });

        $exceptions->render(function (UserNotDefinedException $e, \Illuminate\Http\Request $request) {
            // Jangan tangani untuk route login/register
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register');

            if ($isAuthRoute) {
                return null;
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.user_data_not_found'),
                ],
                401,
            );
        });

        $exceptions->render(function (AuthorizationException $e, \Illuminate\Http\Request $request) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.forbidden'),
                ],
                403,
            );
        });

        $exceptions->render(function (ValidationException $e, \Illuminate\Http\Request $request) {
            $errors = $e->errors();

            $isLoginRoute =
              $request->is('api/v1/auth/login') ||
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
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => __('messages.invalid_credentials'),
                            'errors' => $errors,
                        ],
                        422,
                    );
                }
            }

            return response()->json(
                [
                    'status' => 'error',
                    'message' => __('messages.validation_failed'),
                    'errors' => $errors,
                ],
                422,
            );
        });

        $exceptions->render(function (
            \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->is('api/*') || $request->is('v1/*')) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => __('messages.not_found'),
                    ],
                    404,
                );
            }
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request,
        ) {
            if ($request->is('api/*') || $request->is('v1/*')) {
                $prev = $e->getPrevious();
                if ($prev instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(
                        [
                            'status' => 'error',
                            'message' => __('messages.not_found'),
                        ],
                        404,
                    );
                }

                return response()->json(
                    [
                        'status' => 'error',
                        'message' => __('messages.not_found'),
                    ],
                    404,
                );
            }
        });
    })
    ->create();
