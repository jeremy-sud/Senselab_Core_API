<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Trait HasSafeErrorHandling
 *
 * Proporciona manejo seguro de errores para controladores API.
 *
 * FASE 1.3: Logging estructurado con trace_id, contexto de usuario,
 * IP address y timestamps ISO8601 para auditoría y debugging.
 *
 * En producción, oculta detalles de excepciones del cliente.
 * En desarrollo, expone información para debugging.
 *
 * @package App\Traits
 * @author Sistemas Ursol S.A.
 */
trait HasSafeErrorHandling
{
    /**
     * Genera una respuesta de error segura para el cliente.
     * En producción, no expone detalles de la excepción.
     *
     * FASE 1.3: Logging estructurado a canal 'security' con JSON
     *
     * @param \Throwable $exception
     * @param string $defaultMessage Mensaje genérico para producción
     * @param int $statusCode Código HTTP de respuesta
     * @param array<string, mixed> $context Contexto adicional para logging
     * @return JsonResponse
     */
    protected function safeErrorResponse(
        \Throwable $exception,
        string $defaultMessage = 'Error interno del servidor',
        int $statusCode = 500,
        array $context = []
    ): JsonResponse {
        // Generar o recuperar trace ID para correlacionar logs
        $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();
        
        // Obtener contexto de usuario
        $userId = auth()->id();
        $userEmail = auth()->user()?->email;

        // Loguear el error con contexto completo en canal 'security'
        Log::channel('security')->error('http.error.exception', array_merge([
            'trace_id' => $traceId,
            'user_id' => $userId,
            'user_email' => $userEmail,
            'ip' => request()->ip(),
            'method' => request()->method(),
            'path' => request()->path(),
            'exception' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'status_code' => $statusCode,
            'timestamp' => now()->toIso8601String(),
        ], $context));

        // En producción, ocultar detalles
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => $defaultMessage,
                'trace_id' => $traceId,  // Útil para support técnico
            ], $statusCode)
            ->header('X-Trace-ID', $traceId);
        }

        // En desarrollo, mostrar detalles para debugging
        return response()->json([
            'success' => false,
            'message' => $defaultMessage,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace_id' => $traceId,
        ], $statusCode)
        ->header('X-Trace-ID', $traceId);
    }

    /**
     * Genera una respuesta de error de validación.
     *
     * @param string $message
     * @param array<string, array<string>> $errors
     * @return JsonResponse
     */
    protected function validationErrorResponse(string $message, array $errors = []): JsonResponse
    {
        $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();

        Log::channel('security')->warning('http.error.validation', [
            'trace_id' => $traceId,
            'user_id' => auth()->id(),
            'path' => request()->path(),
            'errors' => array_keys($errors),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'trace_id' => $traceId,
        ], 422)
        ->header('X-Trace-ID', $traceId);
    }

    /**
     * Genera una respuesta de error 404.
     *
     * @param string $resource Nombre del recurso no encontrado
     * @return JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Recurso'): JsonResponse
    {
        $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();

        Log::channel('security')->info('http.error.not_found', [
            'trace_id' => $traceId,
            'user_id' => auth()->id(),
            'path' => request()->path(),
            'resource' => $resource,
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => false,
            'message' => "{$resource} no encontrado",
            'trace_id' => $traceId,
        ], 404)
        ->header('X-Trace-ID', $traceId);
    }

    /**
     * Genera una respuesta de error de autorización.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();

        Log::channel('security')->warning('http.error.unauthorized', [
            'trace_id' => $traceId,
            'user_id' => auth()->id(),
            'path' => request()->path(),
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'trace_id' => $traceId,
        ], 403)
        ->header('X-Trace-ID', $traceId);
    }
}
