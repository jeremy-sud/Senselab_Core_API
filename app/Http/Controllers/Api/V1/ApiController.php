<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador base para APIs v1
 *
 * Proporciona métodos helper para respuestas consistentes en JSON.
 *
 * @package App\Http\Controllers\Api\V1
 */
class ApiController extends Controller
{
    /**
     * Respuesta de éxito
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code, $headers);
    }

    /**
     * Respuesta de error
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code, $headers);
    }

    /**
     * Respuesta de validación fallida
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Respuesta sin encontrado
     */
    protected function notFound(string $message = 'Not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Respuesta no autorizado
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 401);
    }

    /**
     * Respuesta prohibido
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Respuesta de lista paginada
     */
    protected function paginated($items, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'count' => $items->count(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'total_pages' => $items->lastPage(),
            ],
        ], 200);
    }
}
