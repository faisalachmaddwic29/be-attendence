<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('LogHelper', function () {
            return require_once app_path('Helpers/LogHelper.php');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
