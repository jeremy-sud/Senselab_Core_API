<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

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
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @param array<string, string> $headers
     * @return JsonResponse
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $code = 200,
        array $headers = []
    ): JsonResponse {
        // Respuesta estándar para todas las APIs: estructura consistente
        // { success: true, message: '', data: ... }
        // Esto facilita el consumo por clientes front-end y móviles.
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code, $headers);
    }

    /**
     * Respuesta de error
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @param array<string, string> $headers
     * @return JsonResponse
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

        // Error response standardized. Optionally incluye detalles de
        // validación o información adicional en `errors` para debug.
        return response()->json($response, $code, $headers);
    }

    /**
     * Respuesta de validación fallida
     *
     * @param array<string, array<int, string>> $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        // Respuesta específica para errores de validación. Usar cuando
        // la carga del request no cumple las reglas de negocio.
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
     *
     * @param LengthAwarePaginator<int, mixed> $items
     */
    protected function paginated(LengthAwarePaginator $items, string $message = 'Success'): JsonResponse
    {
        // Formato de respuesta para listas paginadas. Devuelve los items
        // y un bloque `pagination` con metadatos útiles para la UI.
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
