<?php

namespace App\Providers;

use App\Contracts\PhotoProcessorContract;
use App\Contracts\SubmissionServiceContract;
use App\Actions\Tenant\ProcessPhotoAction;
use App\Services\SubmissionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PhotoProcessorContract::class, ProcessPhotoAction::class);
        $this->app->bind(SubmissionServiceContract::class, SubmissionService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, \App\Listeners\SendOtpOnLogin::class);

        $this->registerCentralRoutes();
    }

    protected function registerCentralRoutes(): void
    {
        if (file_exists(base_path('routes/central.php'))) {
            Route::middleware('web')
                ->group(base_path('routes/central.php'));
        }
    }
}
