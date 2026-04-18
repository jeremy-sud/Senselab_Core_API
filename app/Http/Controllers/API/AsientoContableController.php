<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsientoContableRequest;
use App\Http\Requests\UpdateAsientoContableRequest;
use App\Http\Resources\AsientoContableResource;
use App\Models\AsientoContable;
use App\Services\AsientoContableService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * AsientoContableController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado usando Service Layer Pattern
 * Reducción de líneas: 719 → ~200 (-72%)
 *
 * Refactorización completada: 13 de febrero de 2026
 */
class AsientoContableController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private AsientoContableService $asientoService) {}

    /**
     * GET /api/asientos-contables
     * Listar asientos contables con paginación y filtros
     */
    #[OA\Get(
        path: '/api/asientos-contables',
        summary: 'Listar asientos contables',
        description: 'Obtiene listado paginado de asientos con filtros por estado, fechas y cuenta contable',
        security: [['sanctum' => []]],
        tags: ['Contabilidad'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AsientoContable::class);
        $this->resolveEmpresaOrFail($request->input('empresa_id'));

        $perPage = (int) $request->input('per_page', 15);

        $asientos = match (true) {
            $request->filled('estado')
                => $this->asientoService->porEstado($request->input('estado'), $perPage),

            $request->filled(['desde', 'hasta'])
                => $this->asientoService->entreFechas(
                    new \DateTime($request->input('desde')),
                    new \DateTime($request->input('hasta')),
                    $perPage
                ),

            $request->filled('cuenta_contable_id')
                => $this->asientoService->porCuenta((int) $request->input('cuenta_contable_id'), $perPage),

            default => $this->asientoService->listar($perPage)
        };

        return AsientoContableResource::collection($asientos);
    }

    /**
     * POST /api/asientos-contables
     * Crear nuevo asiento contable
     */
    #[OA\Post(
        path: '/api/asientos-contables',
        summary: 'Crear asiento contable',
        description: 'Registra un nuevo asiento contable con detalles. El debe debe ser igual al haber',
        security: [['sanctum' => []]],
        tags: ['Contabilidad'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreAsientoContableRequest $request): JsonResponse
    {
        $this->authorize('create', AsientoContable::class);

        try {
            $asiento = $this->asientoService->crear($request->validated());

            return response()->json([
                'message' => 'Asiento contable creado exitosamente',
                'data' => AsientoContableResource::make($asiento)->resolve()
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al crear asiento contable',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 422);
        }
    }

    /**
     * GET /api/asientos-contables/{id}
     * Obtener detalle de un asiento
     */
    #[OA\Get(
        path: '/api/asientos-contables/{id}',
        summary: 'Obtener asiento contable',
        description: 'Retorna los datos completos de un asiento con sus detalles y cuentas',
        security: [['sanctum' => []]],
        tags: ['Contabilidad'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $asiento = $this->asientoService->obtener($id);

        if (!$asiento) {
            return response()->json(['message' => 'Asiento no encontrado'], 404);
        }

        $this->authorize('view', $asiento);
        $this->assertEmpresa($asiento);

        return response()->json(AsientoContableResource::make($asiento)->resolve());
    }

    /**
     * PUT /api/asientos-contables/{id}
     * Actualizar asiento contable
     */
    #[OA\Put(
        path: '/api/asientos-contables/{id}',
        summary: 'Actualizar asiento contable',
        description: 'Modifica un asiento existente. No permite modificar asientos mayorizados',
        security: [['sanctum' => []]],
        tags: ['Contabilidad'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateAsientoContableRequest $request, int $id): JsonResponse
    {
        $asiento = $this->asientoService->obtener($id);

        if (!$asiento) {
            return response()->json(['message' => 'Asiento no encontrado'], 404);
        }

        $this->authorize('update', $asiento);
        $this->assertEmpresa($asiento);

        if ($asiento->estado === 'Mayorizado') {
            return response()->json(['message' => 'No se puede modificar un asiento mayorizado'], 422);
        }

        try {
            $asiento = $this->asientoService->actualizar($asiento, $request->validated());

            return response()->json([
                'message' => 'Asiento actualizado exitosamente',
                'data' => AsientoContableResource::make($asiento)->resolve()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar asiento',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 422);
        }
    }

    /**
     * DELETE /api/asientos-contables/{id}
     * Eliminar asiento contable (soft delete)
     */
    #[OA\Delete(
        path: '/api/asientos-contables/{id}',
        summary: 'Eliminar asiento contable',
        description: 'Realiza soft delete del asiento. No permite eliminar asientos mayorizados',
        security: [['sanctum' => []]],
        tags: ['Contabilidad'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $asiento = $this->asientoService->obtener($id);

        if (!$asiento) {
            return response()->json(['message' => 'Asiento no encontrado'], 404);
        }

        $this->authorize('delete', $asiento);
        $this->assertEmpresa($asiento);

        if ($asiento->estado === 'Mayorizado') {
            return response()->json(['message' => 'No se puede eliminar un asiento mayorizado'], 422);
        }

        try {
            $this->asientoService->eliminar($asiento);

            return response()->json(['message' => 'Asiento eliminado exitosamente']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar asiento',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}
