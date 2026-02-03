<?php

declare(strict_types=1);

namespace Modules\Mail\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Mail\Services\MailService;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailService::class, function ($app) {
            return new MailService();
        });

        $this->app->alias(MailService::class, 'mail.service');
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
    }

    private function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/config.php' => config_path('mail-module.php'),
        ], 'mail-config');
    }

    private function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'mail');
    }
}
