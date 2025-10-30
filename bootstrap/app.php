<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        App\Providers\AuthorizationServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\ObservabilityServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add global middleware here if needed
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Customize exception rendering here if needed
    })
    ->create();

