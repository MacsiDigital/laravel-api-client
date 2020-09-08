<?php

namespace MacsiDigital\API\Providers;

use Illuminate\Support\ServiceProvider;
use MacsiDigital\API\Support\Entry;

class APIServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the main class to use with the facade
        $this->app->bind('api.client', 'Illuminate\Http\Client\Factory');

        // $this->app->bind(Entry::class, function ($app) {
        //     return new Entry();
        // });
    }
}
