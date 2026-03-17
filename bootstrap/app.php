<?php

use App\Exceptions\DomainException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

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
            'cors.advanced' => \App\Http\Middleware\HandleCorsAdvanced::class,
            'log.request' => \App\Http\Middleware\LogRequest::class,
            'throttle.granular' => \App\Http\Middleware\ThrottleRequestsWithRetryAfter::class,
            'metrics.request' => \App\Http\Middleware\RequestMetricsMiddleware::class,
        ]);

        // CORS - Cross-Origin Resource Sharing (FASE 1.2)
        // Middleware nativo de Laravel que respeta config/cors.php
        // Debe ejecutarse PRIMERO para procesar preflight requests (OPTIONS)
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Rate Limiting Granular (FASE 1.5)
        // Se ejecuta SEGUNDO para detectar abuso antes de procesar solicitudes
        $middleware->append(\App\Http\Middleware\ThrottleRequestsWithRetryAfter::class);

        // Request Logging - Logging estructurado (FASE 1.3)
        // Registra entrada/salida de requests con trace_id para correlación
        $middleware->append(\App\Http\Middleware\LogRequest::class);

        // Request Metrics - Métricas de requests HTTP (FASE 2.3)
        // Registra duración, memoria, status codes para observabilidad
        $middleware->append(\App\Http\Middleware\RequestMetricsMiddleware::class);

        // CORS Avanzado - Logging y auditoría personalizada (FASE 1.2)
        // Se ejecuta DESPUÉS de HandleCors para registrar detalles de CORS
        $middleware->append(\App\Http\Middleware\HandleCorsAdvanced::class);

        // Security Headers - OWASP Top 10 compliance (FASE 1.2)
        // Se ejecuta al final para garantizar que se apliquen a todas las respuestas
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Sprint 8.5 - Rate Limiting (Legacy - kept for backward compatibility)
        // Los rate limiters personalizados se configuran en AppServiceProvider::boot()
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // FASE 15: Mapeo centralizado de excepciones de dominio a HTTP responses
        $exceptions->renderable(function (DomainException $e): JsonResponse {
            $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();

            $response = [
                'success' => false,
                'message' => $e->getMessage(),
                'code' => $e->getHttpStatusCode(),
                'trace_id' => $traceId,
            ];

            if (config('app.debug')) {
                $response['exception'] = get_class($e);
                $response['file'] = $e->getFile();
                $response['line'] = $e->getLine();
            }

            return response()->json($response, $e->getHttpStatusCode())
                ->withHeaders(['X-Trace-ID' => $traceId]);
        });
    })->create();
