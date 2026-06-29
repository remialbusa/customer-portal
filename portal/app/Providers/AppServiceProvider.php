<?php

namespace App\Providers;

use App\Services\MondayClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Single MondayClient instance per request, built from config.
        $this->app->singleton(MondayClient::class, fn () => MondayClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
