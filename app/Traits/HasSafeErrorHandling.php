<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasSafeErrorHandling
 * 
 * Proporciona manejo seguro de errores para controladores API.
 * En producción, oculta detalles de excepciones del cliente.
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
        // Loguear el error con contexto completo
        Log::error($defaultMessage, array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $context));

        // En producción, ocultar detalles
        if (app()->environment('production')) {
            return response()->json([
                'success' => false,
                'message' => $defaultMessage,
            ], $statusCode);
        }

        // En desarrollo, mostrar detalles para debugging
        return response()->json([
            'success' => false,
            'message' => $defaultMessage,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ], $statusCode);
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
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Genera una respuesta de error 404.
     *
     * @param string $resource Nombre del recurso no encontrado
     * @return JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Recurso'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => "{$resource} no encontrado",
        ], 404);
    }

    /**
     * Genera una respuesta de error de autorización.
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'No autorizado'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }
}
