<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Google Cloud Storage as singleton
        $this->app->singleton(\App\Services\GoogleCloudStorage::class, function ($app) {
            return new \App\Services\GoogleCloudStorage();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
