<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TransportProviderAdapterInterface;
use App\Services\HttpTransportProviderAdapter;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('manage-portal-users', function ($user = null) {
            // Use the authenticated user (passed or from Auth facade)
            $user = $user ?: auth()->user();
            return $user && $user->portal_role === 'ADMIN_SUPER' && $user->portal_enabled;
        });
    }
}
