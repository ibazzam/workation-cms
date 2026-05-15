<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/vendor-operations.php'));
            Route::middleware('web')->group(base_path('routes/finance/ledger.php'));
            Route::middleware('web')->group(base_path('routes/finance/payouts.php'));
            Route::middleware('web')->group(base_path('routes/finance/refunds.php'));
            Route::middleware('web')->group(base_path('routes/finance/disputes.php'));
        },
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            '/portal/vendor/oauth/facebook/data-deletion',
            '/booking/payment/webhooks/*',
            '/channel/webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
