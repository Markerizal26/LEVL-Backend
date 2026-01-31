<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use App\Support\Traits\RegistersModuleConfig;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Contracts\Repositories\AuthRepositoryInterface;
use Modules\Auth\Contracts\Services\AuthServiceInterface;
use Modules\Auth\Models\User;
use Modules\Auth\Observers\UserObserver;
use Modules\Auth\Repositories\AuthRepository;
use Modules\Auth\Services\AuthService;
use Nwidart\Modules\Traits\PathNamespace;

class AuthServiceProvider extends ServiceProvider
{
    use PathNamespace, RegistersModuleConfig;

    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        User::observe(UserObserver::class);

        $this->app['auth']->provider('trashable-eloquent', function ($app, array $config) {
            return new \Modules\Auth\Support\TrashableEloquentUserProvider($app['hash'], $config['model']);
        });
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(AuthRepositoryInterface::class, AuthRepository::class);

        $this->app->singleton(
            \Modules\Auth\Contracts\Repositories\UserBulkRepositoryInterface::class,
            \Modules\Auth\Repositories\UserBulkRepository::class,
        );

        $this->app->singleton(
            \Modules\Auth\Contracts\Repositories\ProfileAuditLogRepositoryInterface::class,
            \Modules\Auth\Repositories\ProfileAuditLogRepository::class,
        );

        $this->app->singleton(
            \Modules\Auth\Contracts\Repositories\PasswordResetTokenRepositoryInterface::class,
            \Modules\Auth\Repositories\PasswordResetTokenRepository::class,
        );

        $this->app->singleton(
            \Modules\Auth\Services\UserCacheService::class,
            \Modules\Auth\Services\UserCacheService::class,
        );

        $this->app->singleton(
            \Modules\Auth\Contracts\Services\LoginThrottlingServiceInterface::class,
            \Modules\Auth\Services\LoginThrottlingService::class,
        );

        $this->app->scoped(AuthServiceInterface::class, AuthService::class);

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\AuthenticationServiceInterface::class,
            \Modules\Auth\Services\AuthenticationService::class,
        );

        $this->app->scoped(
            \App\Contracts\Services\ProfileServiceInterface::class,
            \Modules\Auth\Services\ProfileService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\EmailVerificationServiceInterface::class,
            \Modules\Auth\Services\EmailVerificationService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\ProfilePrivacyServiceInterface::class,
            \Modules\Auth\Services\ProfilePrivacyService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\UserActivityServiceInterface::class,
            \Modules\Auth\Services\UserActivityService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\UserBulkServiceInterface::class,
            \Modules\Auth\Services\UserBulkService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\Services\UserManagementServiceInterface::class,
            \Modules\Auth\Services\UserManagementService::class,
        );

        $this->app->scoped(
            \Modules\Auth\Contracts\UserAccessPolicyInterface::class,
            \Modules\Auth\Policies\UserAccessPolicy::class,
        );

        $this->app->singleton(
            \Modules\Auth\Repositories\BenchmarkRepository::class,
            \Modules\Auth\Repositories\BenchmarkRepository::class,
        );

        $this->app->scoped(
            \Modules\Auth\Services\BenchmarkService::class,
            \Modules\Auth\Services\BenchmarkService::class,
        );
    }

    protected function registerCommands(): void {}

    protected function registerCommandSchedules(): void {}

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $this->registerModuleConfig();
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(
            array_merge($this->getPublishableViewPaths(), [$sourcePath]),
            $this->nameLower,
        );

        Blade::componentNamespace(
            config('modules.namespace').'\\'.$this->name.'\\View\\Components',
            $this->nameLower,
        );
    }

    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
