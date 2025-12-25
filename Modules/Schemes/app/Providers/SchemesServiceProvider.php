<?php

namespace Modules\Schemes\Providers;

use App\Support\Traits\RegistersModuleConfig;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class SchemesServiceProvider extends ServiceProvider
{
    use PathNamespace, RegistersModuleConfig;

    protected string $name = 'Schemes';

    protected string $nameLower = 'schemes';

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
        $this->registerPolicies();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
    }

    /**
     * Register policies.
     */
    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Schemes\Models\Course::class,
            \Modules\Schemes\Policies\CoursePolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Schemes\Models\Unit::class,
            \Modules\Schemes\Policies\UnitPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Schemes\Models\Lesson::class,
            \Modules\Schemes\Policies\LessonPolicy::class
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind service interfaces
        $this->app->bind(
            \Modules\Schemes\Contracts\Services\CourseServiceInterface::class,
            \Modules\Schemes\Services\CourseService::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Services\UnitServiceInterface::class,
            \Modules\Schemes\Services\UnitService::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Services\LessonServiceInterface::class,
            \Modules\Schemes\Services\LessonService::class
        );

        // Bind repositories
        $this->app->bind(
            \Modules\Schemes\Contracts\Repositories\CourseRepositoryInterface::class,
            \Modules\Schemes\Repositories\CourseRepository::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Repositories\LessonRepositoryInterface::class,
            \Modules\Schemes\Repositories\LessonRepository::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Repositories\UnitRepositoryInterface::class,
            \Modules\Schemes\Repositories\UnitRepository::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Repositories\LessonBlockRepositoryInterface::class,
            \Modules\Schemes\Repositories\LessonBlockRepository::class
        );
        $this->app->bind(
            \Modules\Schemes\Contracts\Repositories\TagRepositoryInterface::class,
            \Modules\Schemes\Repositories\TagRepository::class
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

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
