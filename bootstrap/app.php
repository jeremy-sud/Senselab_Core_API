<?php

// Polyfill para la extensión bcmath si no está instalada en el entorno actual
if (!function_exists('bcadd')) {
    function bcadd($num1, $num2, $scale = null) {
        $res = (float)$num1 + (float)$num2;
        return $scale !== null ? number_format($res, $scale, '.', '') : (string)$res;
    }
}
if (!function_exists('bcsub')) {
    function bcsub($num1, $num2, $scale = null) {
        $res = (float)$num1 - (float)$num2;
        return $scale !== null ? number_format($res, $scale, '.', '') : (string)$res;
    }
}
if (!function_exists('bcmul')) {
    function bcmul($num1, $num2, $scale = null) {
        $res = (float)$num1 * (float)$num2;
        return $scale !== null ? number_format($res, $scale, '.', '') : (string)$res;
    }
}
if (!function_exists('bcdiv')) {
    function bcdiv($num1, $num2, $scale = null) {
        if ((float)$num2 == 0.0) return '0';
        $res = (float)$num1 / (float)$num2;
        return $scale !== null ? number_format($res, $scale, '.', '') : (string)$res;
    }
}
if (!function_exists('bccomp')) {
    function bccomp($num1, $num2, $scale = null) {
        $n1 = (float)$num1;
        $n2 = (float)$num2;
        if ($scale !== null) {
            $n1 = round($n1, $scale);
            $n2 = round($n2, $scale);
        }
        return $n1 <=> $n2;
    }
}

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
        // Confiar en todos los proxies (Cloudflare, AWS ALB) para leer cabeceras HTTPS X-Forwarded-Proto correctamente
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'cors.advanced' => \App\Http\Middleware\HandleCorsAdvanced::class,
            'log.request' => \App\Http\Middleware\LogRequest::class,
            'throttle.granular' => \App\Http\Middleware\ThrottleRequestsWithRetryAfter::class,
            'metrics.request' => \App\Http\Middleware\RequestMetricsMiddleware::class,
            // FASE 22: Escalabilidad
            'etag' => \App\Http\Middleware\ETagMiddleware::class,
            'tracing' => \App\Http\Middleware\TracingMiddleware::class,
            'sunset.monitor' => \App\Http\Middleware\SunsetMonitorMiddleware::class,
            // Suscripciones y Límites SaaS (Hito 3)
            'enforce.limits' => \App\Http\Middleware\EnforceTenantPlanLimits::class,
        ]);

        // CORS - Cross-Origin Resource Sharing (FASE 1.2)
        // Middleware nativo de Laravel que respeta config/cors.php
        // Debe ejecutarse PRIMERO para procesar preflight requests (OPTIONS)
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);

        // Distributed Tracing (FASE 22 - Escalabilidad)
        // Propaga trace IDs y genera spans para cada request
        $middleware->append(\App\Http\Middleware\TracingMiddleware::class);

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

        // ETag para conditional GET (FASE 22 - Escalabilidad)
        // Genera ETags y responde 304 cuando el contenido no ha cambiado
        $middleware->append(\App\Http\Middleware\ETagMiddleware::class);

        // Security Headers - OWASP Top 10 compliance (FASE 1.2)
        // Se ejecuta al final para garantizar que se apliquen a todas las respuestas
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Sunset Monitor - Alertas de obsolescencia de API (RFC 8594)
        $middleware->append(\App\Http\Middleware\SunsetMonitorMiddleware::class);
        
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
