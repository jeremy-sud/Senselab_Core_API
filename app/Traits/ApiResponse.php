<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Trait ApiResponse — FASE 15
 *
 * Envelope unificado para todas las respuestas API.
 * Formato: {success, message?, data?, errors?, meta?, trace_id}
 *
 * Reemplaza HasSafeErrorHandling y los 3 formatos inconsistentes.
 */
trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = '',
        int $statusCode = 200
    ): JsonResponse {
        $response = ['success' => true];

        if ($message !== '') {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function createdResponse(
        mixed $data = null,
        string $message = 'Recurso creado exitosamente'
    ): JsonResponse {
        return $this->successResponse($data, $message, 201);
    }

    /** @param LengthAwarePaginator<int, mixed> $paginator */
    protected function paginatedResponse(
        LengthAwarePaginator $paginator,
        string $resourceClass = ''
    ): JsonResponse {
        $data = $resourceClass !== ''
            ? $resourceClass::collection($paginator->items())
            : $paginator->items();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    protected function errorResponse(
        string $message = 'Error interno del servidor',
        int $statusCode = 500,
        mixed $errors = null
    ): JsonResponse {
        $traceId = request()->header('X-Trace-ID') ?? (string) Str::uuid();

        $response = [
            'success' => false,
            'message' => $message,
            'trace_id' => $traceId,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode)
            ->withHeaders(['X-Trace-ID' => $traceId]);
    }

    protected function deletedResponse(string $message = 'Recurso eliminado exitosamente'): JsonResponse
    {
        return $this->successResponse(message: $message);
    }
}
