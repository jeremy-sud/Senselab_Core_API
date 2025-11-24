<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        ]);
        
        // Sprint 8.5 - Rate Limiting
        // Configurar límites de tasa para proteger la API
        $middleware->throttleApi();
        
        // Rate limiters personalizados
        RateLimiter::for('api', function (Request $request) {
            // 60 requests por minuto para usuarios autenticados
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        
        RateLimiter::for('reports', function (Request $request) {
            // 10 requests por minuto para reportes pesados
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        
        RateLimiter::for('imports', function (Request $request) {
            // 5 requests por minuto para importaciones
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
        
        RateLimiter::for('hacienda', function (Request $request) {
            // 20 requests por minuto para Hacienda API
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
