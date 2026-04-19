<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Distributed Tracing Middleware
 *
 * FASE 22 - Escalabilidad
 *
 * Implementa context propagation para distributed tracing.
 * Genera/propaga trace IDs a través de requests HTTP.
 *
 * Headers de entrada (contexto del cliente/servicio upstream):
 * - X-Trace-Id: ID de la traza completa
 * - X-Span-Id: ID del span padre
 *
 * Headers de salida (agregados a la respuesta):
 * - X-Trace-Id: ID de la traza (mismo que entrada o nuevo)
 * - X-Span-Id: ID del span de este request
 * - X-Response-Time: Tiempo de respuesta en ms
 *
 * Logging:
 * - Agrega trace_id y span_id al contexto de logs
 * - Registra inicio y fin de cada request con métricas
 *
 * @package App\Http\Middleware
 * @version 5.0.0
 */
class TracingMiddleware
{
    /**
     * Headers de contexto de tracing.
     */
    private const string HEADER_TRACE_ID = 'X-Trace-Id';
    private const string HEADER_SPAN_ID = 'X-Span-Id';
    private const string HEADER_PARENT_SPAN_ID = 'X-Parent-Span-Id';
    private const string HEADER_RESPONSE_TIME = 'X-Response-Time';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Obtener o generar trace ID
        $traceId = $request->header(self::HEADER_TRACE_ID) ?? $this->generateTraceId();

        // Obtener parent span ID si existe
        $parentSpanId = $request->header(self::HEADER_SPAN_ID);

        // Generar span ID para este request
        $spanId = $this->generateSpanId();

        // Almacenar en request para uso posterior
        $request->attributes->set('trace_id', $traceId);
        $request->attributes->set('span_id', $spanId);
        $request->attributes->set('parent_span_id', $parentSpanId);

        // Agregar al contexto de logging
        Log::shareContext([
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_span_id' => $parentSpanId,
        ]);

        // Log inicio del request (si tracing está habilitado)
        if (config('tracing.enabled', false)) {
            $this->logRequestStart($request, $traceId, $spanId, $parentSpanId);
        }

        /** @var Response $response */
        $response = $next($request);

        // Calcular tiempo de respuesta
        $responseTimeMs = (microtime(true) - $startTime) * 1000;

        // Agregar headers de tracing a la respuesta
        $response->headers->set(self::HEADER_TRACE_ID, $traceId);
        $response->headers->set(self::HEADER_SPAN_ID, $spanId);
        $response->headers->set(self::HEADER_RESPONSE_TIME, sprintf('%.2fms', $responseTimeMs));

        if ($parentSpanId !== null) {
            $response->headers->set(self::HEADER_PARENT_SPAN_ID, $parentSpanId);
        }

        // Log fin del request (si tracing está habilitado)
        if (config('tracing.enabled', false)) {
            $this->logRequestEnd($request, $response, $traceId, $spanId, $responseTimeMs);
        }

        return $response;
    }

    /**
     * Genera un Trace ID único (32 caracteres hex, compatible con W3C Trace Context).
     */
    private function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Genera un Span ID único (16 caracteres hex, compatible con W3C Trace Context).
     */
    private function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Log del inicio del request con contexto de tracing.
     */
    private function logRequestStart(
        Request $request,
        string $traceId,
        string $spanId,
        ?string $parentSpanId
    ): void {
        Log::debug('Request started', [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
            'tenant_id' => $request->user()?->empresa_id,
            'ip' => $request->ip(),
            'user_agent' => Str::limit($request->userAgent() ?? '', 100),
        ]);
    }

    /**
     * Log del fin del request con métricas.
     */
    private function logRequestEnd(
        Request $request,
        Response $response,
        string $traceId,
        string $spanId,
        float $responseTimeMs
    ): void {
        $logLevel = $this->determineLogLevel($response->getStatusCode(), $responseTimeMs);

        Log::log($logLevel, 'Request completed', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'response_time_ms' => round($responseTimeMs, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ]);
    }

    /**
     * Determina el nivel de log según status code y tiempo de respuesta.
     */
    private function determineLogLevel(int $statusCode, float $responseTimeMs): string
    {
        // Errores de servidor → error
        if ($statusCode >= 500) {
            return 'error';
        }

        // Errores de cliente → warning
        if ($statusCode >= 400) {
            return 'warning';
        }

        // Respuestas lentas (>1s) → warning
        if ($responseTimeMs > 1000) {
            return 'warning';
        }

        // Normal → debug
        return 'debug';
    }
}
