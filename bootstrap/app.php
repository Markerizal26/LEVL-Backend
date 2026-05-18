<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\AuthenticateLoadtestUser;
use App\Http\Middleware\EnsureCleanDatabaseSession;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;


ini_set('memory_limit', env('PHP_MEMORY_LIMIT', '2048M'));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
        
        
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            AuthenticateLoadtestUser::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
            \App\Http\Middleware\EnsureRole::class,
            \App\Http\Middleware\EnsurePermission::class,
        ]);
        
        $middleware->alias([
            'role' => EnsureRole::class,
            'permission' => EnsurePermission::class,
            'ensure.clean.db.session' => EnsureCleanDatabaseSession::class,
            'cache.response' => \App\Http\Middleware\CacheResponse::class,
            'xp.info' => \Modules\Gamification\Http\Middleware\AppendXpInfoToResponse::class,
            'track.daily.login' => \Modules\Gamification\Http\Middleware\TrackDailyLogin::class,
            'ensure.user.active' => \Modules\Auth\Http\Middleware\EnsureUserActive::class,
        ]);

        
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
              \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        
        $middleware->api(prepend: [
            AuthenticateLoadtestUser::class,
            \App\Http\Middleware\SetLocale::class,
            EnsureCleanDatabaseSession::class,
        ]);

        
        $middleware->appendToGroup('api', \Modules\Auth\Http\Middleware\EnsureUserActive::class);

        $middleware->api(prepend: [\Illuminate\Routing\Middleware\ThrottleRequests::class.':api']);

        
        $middleware->api(prepend: [\Illuminate\Http\Middleware\HandleCors::class]);

        
        $middleware->api(append: [\App\Http\Middleware\InjectQueryDetectorStatus::class]);

        $middleware->append(\App\Http\Middleware\LogApiAction::class);
        
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        
        $exceptions->render(function (\App\Exceptions\ResourceNotFoundException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.not_found');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
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
                    'errors' => $e->getErrors(),
                ],
                409,
            );
        });

        $exceptions->render(function (\Spatie\QueryBuilder\Exceptions\InvalidFilterQuery $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 400);
        });

        $exceptions->render(function (\Spatie\QueryBuilder\Exceptions\InvalidSortQuery $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 400);
        });

        $exceptions->render(function (\Spatie\QueryBuilder\Exceptions\InvalidIncludeQuery $e, \Illuminate\Http\Request $request) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 400);
        });

        
        $exceptions->render(function (BusinessException $e, \Illuminate\Http\Request $request) {
            $message = $e->getMessage() ?: __('messages.error');

            return response()->json(
                [
                    'success' => false,
                    'message' => $message,
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
                    'success' => false,
                    'message' => __('messages.unauthenticated'),
                    'errors' => null,
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
                    'success' => false,
                    'message' => __('messages.session_expired'),
                    'errors' => null,
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
                    'success' => false,
                    'message' => __('messages.session_invalid'),
                    'errors' => null,
                ],
                401,
            );
        });

        $exceptions->render(function (TokenBlacklistedException $e, \Illuminate\Http\Request $request) {
            
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
                    'success' => false,
                    'message' => __('messages.session_blacklisted'),
                    'errors' => null,
                ],
                401,
            );
        });

        $exceptions->render(function (JWTException $e, \Illuminate\Http\Request $request) {
            
            $isAuthRoute =
              $request->is('api/v1/auth/login') ||
              $request->is('api/v1/auth/register') ||
              $request->routeIs('auth.login') ||
              $request->routeIs('auth.register') ||
              str_contains($request->path(), 'auth/login') ||
              str_contains($request->path(), 'auth/register');

            if ($isAuthRoute) {
                return null; 
            }

            return response()->json(
                [
                    'success' => false,
                    'message' => __('messages.session_not_found'),
                    'errors' => null,
                ],
                401,
            );
        });

        $exceptions->render(function (UserNotDefinedException $e, \Illuminate\Http\Request $request) {
            
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
                    'success' => false,
                    'message' => __('messages.user_data_not_found'),
                    'errors' => null,
                ],
                401,
            );
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->is('v1/*')) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => __('messages.forbidden'),
                        'errors' => null,
                    ],
                    403,
                );
            }
        });

        $exceptions->render(function (AuthorizationException $e, \Illuminate\Http\Request $request) {
            return response()->json(
                [
                    'success' => false,
                    'message' => __('messages.forbidden'),
                    'errors' => null,
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
                            'success' => false,
                            'message' => __('messages.invalid_credentials'),
                            'errors' => $errors,
                        ],
                        422,
                    );
                }
            }

            return response()->json(
                [
                    'success' => false,
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
                $model = $e->getModel();
                $message = __('messages.not_found');

                
                if ($model) {
                    $modelName = class_basename($model);
                    $message = match ($modelName) {
                        'Course' => __('messages.courses.not_found'),
                        'Unit' => __('messages.units.not_found'),
                        'Lesson' => __('messages.lessons.not_found'),
                        'LessonBlock' => __('messages.lesson_blocks.not_found'),
                        'Tag' => __('messages.tags.not_found'),
                        'User' => __('messages.user.not_found'),
                        'Category' => __('messages.categories.not_found'),
                        'Enrollment' => __('messages.enrollments.not_found'),
                        default => __('messages.not_found'),
                    };
                }

                return response()->json(
                    [
                        'success' => false,
                        'message' => $message,
                        'errors' => null,
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
                $message = __('messages.not_found');

                if ($prev instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $model = $prev->getModel();
                    if ($model) {
                        $modelName = class_basename($model);
                        $message = match ($modelName) {
                            'Course' => __('messages.courses.not_found'),
                            'Unit' => __('messages.units.not_found'),
                            'Lesson' => __('messages.lessons.not_found'),
                            'LessonBlock' => __('messages.lesson_blocks.not_found'),
                            'Tag' => __('messages.tags.not_found'),
                            'User' => __('messages.user.not_found'),
                            'Category' => __('messages.categories.not_found'),
                            'Enrollment' => __('messages.enrollments.not_found'),
                            default => __('messages.not_found'),
                        };
                    }
                }

                return response()->json(
                    [
                        'success' => false,
                        'message' => $message,
                        'errors' => null,
                    ],
                    404,
                );
            }
        });

        $exceptions->render(function (QueryException $e, \Illuminate\Http\Request $request) {
            $sqlState = is_array($e->errorInfo) ? ($e->errorInfo[0] ?? null) : null;

            if ($sqlState !== '25P02') {
                return null;
            }

            $correlationId  = $request->header('X-Request-ID', (string) \Illuminate\Support\Str::uuid());
            $connectionName = DB::getDefaultConnection();

            Log::error('PostgreSQL aborted transaction (25P02) reached exception handler — connection will be reset.', [
                'correlation_id'   => $correlationId,
                'path'             => $request->path(),
                'method'           => $request->method(),
                'sql'              => $e->getSql(),
                'bindings'         => $e->getBindings(),
                'transaction_level' => DB::connection($connectionName)->transactionLevel(),
                'message'          => $e->getMessage(),
            ]);

            $connection = DB::connection($connectionName);
            while ($connection->transactionLevel() > 0) {
                try {
                    $connection->rollBack();
                } catch (\Throwable) {
                    break;
                }
            }

            try {
                $pdo = $connection->getRawPdo();
                if ($pdo instanceof \PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (\Throwable) {
            }

            try {
                DB::purge($connectionName);
                DB::reconnect($connectionName);
            } catch (\Throwable) {
            }

            if ($request->is('api/*') || $request->is('v1/*')) {
                return response()->json([
                    'success'        => false,
                    'message'        => __('messages.error'),
                    'errors'         => null,
                    'correlation_id' => $correlationId,
                ], 500);
            }

            return null;
        });
    })
    ->create();
