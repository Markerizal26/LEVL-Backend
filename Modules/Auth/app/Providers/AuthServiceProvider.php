<?php

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

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

        // Register observers
        User::observe(UserObserver::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);

        $this->app->bind(
            \Modules\Auth\Contracts\Repositories\UserBulkRepositoryInterface::class,
            \Modules\Auth\Repositories\UserBulkRepository::class,
        );

        $this->app->bind(
            \App\Contracts\Services\ProfileServiceInterface::class,
            \Modules\Auth\Services\ProfileService::class,
        );

        // Repository bindings
        $this->app->bind(
            \Modules\Auth\Contracts\Repositories\ProfileAuditLogRepositoryInterface::class,
            \Modules\Auth\Repositories\ProfileAuditLogRepository::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Repositories\PinnedBadgeRepositoryInterface::class,
            \Modules\Auth\Repositories\PinnedBadgeRepository::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Repositories\PasswordResetTokenRepositoryInterface::class,
            \Modules\Auth\Repositories\PasswordResetTokenRepository::class,
        );

        // Service bindings
        $this->app->bind(
            \Modules\Auth\Contracts\Services\EmailVerificationServiceInterface::class,
            \Modules\Auth\Services\EmailVerificationService::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Services\LoginThrottlingServiceInterface::class,
            \Modules\Auth\Services\LoginThrottlingService::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Services\ProfilePrivacyServiceInterface::class,
            \Modules\Auth\Services\ProfilePrivacyService::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Services\ProfileStatisticsServiceInterface::class,
            \Modules\Auth\Services\ProfileStatisticsService::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Services\UserActivityServiceInterface::class,
            \Modules\Auth\Services\UserActivityService::class,
        );

        $this->app->bind(
            \Modules\Auth\Contracts\Services\UserBulkServiceInterface::class,
            \Modules\Auth\Services\UserBulkService::class,
        );
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
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

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->registerModuleConfig();
    }

    /**
     * Register views.
     */
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

    /**
     * Get the services provided by the provider.
     */
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
