<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Security Headers - OWASP Top 10 compliance
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Sprint 8.5 - Rate Limiting
        // Los rate limiters personalizados se configuran en AppServiceProvider::boot()
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
