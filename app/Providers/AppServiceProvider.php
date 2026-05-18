<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\EnrollmentKeyHasherInterface;
use App\Support\EnrollmentKeyHasher;
use App\Support\LoadtestBypass;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EnrollmentKeyHasherInterface::class, EnrollmentKeyHasher::class);
        $this->app->bind(
            \App\Contracts\EnrollmentKeyEncrypterInterface::class,
            \App\Services\EnrollmentKeyEncrypter::class
        );

        $this->app->bind(
            \App\Contracts\Services\ForumServiceInterface::class,
            \Modules\Forums\Services\ForumService::class
        );

        if ($this->app->runningInConsole()) {

            $this->app->singleton('heavy.service', function ($app) {});
        }
    }

    public function boot(): void
    {
        \Illuminate\Database\Connection::resolverFor('pgsql', function ($connection, $database, $prefix, $config) {
            return new \App\Database\PostgresConnection($connection, $database, $prefix, $config);
        });

        $this->configureRateLimiting();
        $this->configureOctaneCompatibility();
        $this->configureQueryFailureLogging();

        \App\Models\ActivityLog::observe(\App\Observers\ActivityLogObserver::class);

        if ($this->app->environment('local') && ! app()->bound(\Laravel\Octane\Octane::class)) {
            DB::listen(function ($query) {
                if ($query->time > 50) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time.'ms',
                        'url' => request()->fullUrl(),
                    ]);
                }
            });
        }

        if ($this->app->environment('local')) {
            Mail::alwaysTo(config('mail.development_to', 'dev@local.test'));
        }

        if (! $this->app->environment('local')) {
            $this->app['view']->getFinder()->setPaths(array_map(function ($path) {
                return $path;
            }, $this->app['view']->getFinder()->getPaths()));
        }
    }

    protected function configureQueryFailureLogging(): void
    {
        DB::listen(function ($query): void {
            if ($query->time > 1000) {
                Log::warning('Very slow query detected (>1s).', [
                    'sql' => $query->sql,
                    'time' => $query->time.'ms',
                    'path' => request()->path(),
                    'method' => request()->method(),
                ]);
            }
        });
    }

    protected function configureOctaneCompatibility(): void
    {
        if (! class_exists(\Laravel\Octane\Events\RequestTerminated::class)) {
            return;
        }

        \Illuminate\Support\Facades\Event::listen(
            \Laravel\Octane\Events\RequestTerminated::class,
            function ($event) {

                auth()->forgetGuards();

                $services = [
                    \Modules\Gamification\Services\GamificationService::class,
                    \Modules\Gamification\Services\BadgeService::class,
                    \Modules\Gamification\Services\LeaderboardService::class,
                    \Modules\Gamification\Services\LevelService::class,
                    \Modules\Gamification\Services\EventCounterService::class,
                    \Modules\Gamification\Services\EventLoggerService::class,
                ];

                foreach ($services as $service) {
                    if ($this->app->resolved($service)) {
                        $this->app->forgetInstance($service);
                    }
                }

                $models = [
                    \Modules\Gamification\Models\Badge::class,
                    \Modules\Gamification\Models\UserGamificationStat::class,
                    \Modules\Gamification\Models\Leaderboard::class,
                    \Modules\Gamification\Models\Point::class,
                ];

                foreach ($models as $model) {
                    if (method_exists($model, 'clearBootedModels')) {
                        $model::clearBootedModels();
                    }
                }
            }
        );
    }

    protected function configureRateLimiting(): void
    {

        RateLimiter::for('api', function (Request $request) {

            if ($this->shouldBypassRateLimiting($request)) {
                return Limit::none();
            }

            if ($this->app->environment('testing')) {
                return Limit::none();
            }

            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinutes(1, 60)->by($key);
        });

        RateLimiter::for('auth', function (Request $request) {
            if ($this->shouldBypassRateLimiting($request)) {
                return Limit::none();
            }

            if ($this->app->environment('testing')) {
                return Limit::none();
            }

            return Limit::perMinutes(1, 10)->by($request->ip());
        });

        RateLimiter::for('enrollment', function (Request $request) {
            if ($this->shouldBypassRateLimiting($request)) {
                return Limit::none();
            }

            if ($this->app->environment('testing')) {
                return Limit::none();
            }

            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinutes(1, 5)->by($key);
        });
    }

    private function shouldBypassRateLimiting(Request $request): bool
    {
        return LoadtestBypass::isEnabled($request);
    }
}
