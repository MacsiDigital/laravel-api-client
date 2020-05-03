<?php

namespace MacsiDigital\API\Providers;

use Illuminate\Support\ServiceProvider;

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
        $this->app->bind('api.client', 'MacsiDigital\API\Support\PendingRequest');
    }
}
