<?php

declare(strict_types=1);

namespace Modules\Learning\Providers;

use App\Support\Traits\RegistersModuleConfig;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Learning\Contracts\Repositories\AssignmentRepositoryInterface;
use Modules\Learning\Contracts\Repositories\OverrideRepositoryInterface;
use Modules\Learning\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Learning\Contracts\Repositories\SubmissionRepositoryInterface;
use Modules\Learning\Contracts\Services\AssignmentServiceInterface;
use Modules\Learning\Contracts\Services\LearningPageServiceInterface;
use Modules\Learning\Contracts\Services\QuestionServiceInterface;
use Modules\Learning\Contracts\Services\ReviewModeServiceInterface;
use Modules\Learning\Contracts\Services\SubmissionServiceInterface;
use Modules\Learning\Repositories\AssignmentRepository;
use Modules\Learning\Repositories\OverrideRepository;
use Modules\Learning\Repositories\QuestionRepository;
use Modules\Learning\Repositories\SubmissionRepository;
use Modules\Learning\Services\AssignmentService;
use Modules\Learning\Services\LearningPageService;
use Modules\Learning\Services\QuestionService;
use Modules\Learning\Services\ReviewModeService;
use Modules\Learning\Services\SubmissionService;
use Nwidart\Modules\Traits\PathNamespace;

class LearningServiceProvider extends ServiceProvider
{
    use PathNamespace, RegistersModuleConfig;

    protected string $name = 'Learning';

    protected string $nameLower = 'learning';

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
            \Modules\Learning\Models\Assignment::class,
            \Modules\Learning\Policies\AssignmentPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Learning\Models\Submission::class,
            \Modules\Learning\Policies\SubmissionPolicy::class
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
        $this->app->bind(AssignmentRepositoryInterface::class, AssignmentRepository::class);
        $this->app->bind(SubmissionRepositoryInterface::class, SubmissionRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(OverrideRepositoryInterface::class, OverrideRepository::class);

        $this->app->bind(AssignmentServiceInterface::class, AssignmentService::class);
        $this->app->bind(SubmissionServiceInterface::class, SubmissionService::class);
        $this->app->bind(LearningPageServiceInterface::class, LearningPageService::class);
        $this->app->bind(QuestionServiceInterface::class, QuestionService::class);
        $this->app->bind(ReviewModeServiceInterface::class, ReviewModeService::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\Learning\Console\Commands\CleanupExpiredFiles::class,
        ]);
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
