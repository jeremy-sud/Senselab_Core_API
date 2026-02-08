<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Logging Estructurado
 *
 * FASE 1.3: Registra todas las requests HTTP con contexto de usuario,
 * duración, errores y anomalías para análisis y auditoría.
 *
 * Genera trace_id único para correlacionar logs correlacionados
 *
 * @see https://twelve-factor.net/logs
 * @see https://docs.sentry.io/product/performance/
 */
class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generar trace ID único para correlacionar logs relacionados
        $traceId = $request->header('X-Trace-ID') ?: (string) Str::uuid();
        $startTime = microtime(true);

        // Obtener contexto de usuario
        $userId = auth()->id();
        $userEmail = auth()->user()?->email;
        $userAgent = $request->userAgent();

        // Log de entrada de request
        Log::channel('security')->info('http.request.started', [
            'trace_id' => $traceId,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->url(),
            'query' => $request->getQueryString(),
            'user_agent' => $userAgent,
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $response = $next($request);

            // Calcular duración en milisegundos
            $duration = (microtime(true) - $startTime) * 1000;

            // Log de salida de request exitosa
            Log::channel('security')->info('http.request.completed', [
                'trace_id' => $traceId,
                'user_id' => $userId,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->status(),
                'duration_ms' => round($duration, 2),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Log de performance si es lenta (> 1000ms)
            if ($duration > 1000) {
                Log::channel('performance')->warning('slow_request', [
                    'trace_id' => $traceId,
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'duration_ms' => round($duration, 2),
                    'user_id' => $userId,
                    'status_code' => $response->status(),
                    'threshold_ms' => 1000,
                ]);
            }

            // Agregar trace ID a la respuesta para debugging
            return $response->header('X-Trace-ID', $traceId);

        } catch (\Throwable $exception) {
            // Calcular duración en caso de excepción
            $duration = (microtime(true) - $startTime) * 1000;

            // Log de error con contexto completo
            Log::channel('security')->error('http.request.failed', [
                'trace_id' => $traceId,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'method' => $request->method(),
                'path' => $request->path(),
                'exception' => get_class($exception),
                'exception_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Re-lanzar excepción para que sea manejada por ExceptionHandler
            throw $exception;
        }
    }
}
