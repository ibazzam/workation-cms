<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TransportProviderAdapterInterface;
use App\Services\HttpTransportProviderAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // bind the transport provider adapter interface to the HTTP stub
        $this->app->bind(TransportProviderAdapterInterface::class, HttpTransportProviderAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
