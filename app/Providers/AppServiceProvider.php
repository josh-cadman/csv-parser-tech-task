<?php

namespace App\Providers;

use App\Interfaces\HomeownerParserInterface;
use App\Services\LocalHomeownerParserService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bind the Homeowner Parser Interface with the local service
        $this->app->bind(HomeownerParserInterface::class, LocalHomeownerParserService::class);
    }
}
