<?php

declare(strict_types=1);

namespace Modules\Enrollments\Providers;

use App\Support\Traits\RegistersModuleConfig;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Enrollments\Contracts\Repositories\EnrollmentRepositoryInterface;
use Modules\Enrollments\Repositories\EnrollmentRepository;
use Modules\Enrollments\Services\EnrollmentService;
use Nwidart\Modules\Traits\PathNamespace;

class EnrollmentsServiceProvider extends ServiceProvider
{
    use PathNamespace, RegistersModuleConfig;

    protected string $name = 'Enrollments';

    protected string $nameLower = 'enrollments';

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

    protected function registerPolicies(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Enrollments\Models\Enrollment::class,
            \Modules\Enrollments\Policies\EnrollmentPolicy::class
        );
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->registerBindings();
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(
            EnrollmentRepositoryInterface::class,
            EnrollmentRepository::class
        );

        $this->app->scoped(
            \Modules\Enrollments\Contracts\Services\EnrollmentServiceInterface::class,
            EnrollmentService::class
        );

        $this->app->scoped(
            \Modules\Enrollments\Contracts\Services\EnrollmentReportServiceInterface::class,
            \Modules\Enrollments\Services\EnrollmentReportService::class
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

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
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
