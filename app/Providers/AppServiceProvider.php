<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Services\CaseService;
use App\Services\DocumentService;
use App\Services\DocumentValidationService;
use App\Services\NotificationService;
use App\Services\WorkflowService;
use App\Utils\ApplicationUtils;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(CaseService::class);
        $this->app->singleton(DocumentService::class);
        $this->app->singleton(DocumentValidationService::class);
        $this->app->singleton(WorkflowService::class);
        $this->app->singleton('ApplicationUtils', ApplicationUtils::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            \URL::forceScheme('https');
            // Force secure assets in production
            \URL::forceRootUrl(config('app.url'));
        }
    }
}
