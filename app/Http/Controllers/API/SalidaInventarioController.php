<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalidaInventarioRequest;
use App\Http\Requests\UpdateSalidaInventarioRequest;
use App\Http\Resources\SalidaInventarioResource;
use App\Models\SalidaInventario;
use App\Services\SalidaInventarioService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * SalidaInventarioController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado usando Service Layer Pattern
 * Reducción de líneas: 710 → ~220 (-69%)
 *
 * Refactorización completada: 13 de febrero de 2026
 */
class SalidaInventarioController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private SalidaInventarioService $salidaService) {}

    /**
     * GET /api/salidas-inventario
     * Listar salidas con paginación y filtros opcionales
     */
    #[OA\Get(
        path: '/api/salidas-inventario',
        summary: 'Listar salidas de inventario',
        description: 'Obtiene listado paginado de salidas con filtros opcionales por cliente, almacén y fechas',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SalidaInventario::class);
        $this->resolveEmpresaOrFail($request->input('empresa_id'));

        $perPage = (int) $request->input('per_page', 15);

        $salidas = match (true) {
            $request->filled('cliente_id')
                => $this->salidaService->porCliente((int) $request->input('cliente_id'), $perPage),

            $request->filled('almacen_id')
                => $this->salidaService->porAlmacen((int) $request->input('almacen_id'), $perPage),

            $request->filled(['fecha_inicio', 'fecha_fin'])
                => $this->salidaService->entreFechas(
                    new \DateTime($request->input('fecha_inicio')),
                    new \DateTime($request->input('fecha_fin')),
                    $perPage
                ),

            default => $this->salidaService->listar($perPage)
        };

        return SalidaInventarioResource::collection($salidas);
    }

    /**
     * POST /api/salidas-inventario
     * Crear nueva salida de inventario
     */
    #[OA\Post(
        path: '/api/salidas-inventario',
        summary: 'Crear salida de inventario',
        description: 'Registra una nueva salida de inventario en estado Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreSalidaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', SalidaInventario::class);

        $salida = $this->salidaService->crear($request->validated());

        return $this->createdResponse(
            SalidaInventarioResource::make($salida)->resolve(),
            'Salida de inventario creada exitosamente'
        );
    }

    /**
     * GET /api/salidas-inventario/{id}
     * Obtener detalle de una salida
     */
    #[OA\Get(
        path: '/api/salidas-inventario/{id}',
        summary: 'Obtener salida de inventario',
        description: 'Retorna los datos de una salida específica con sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $salida = $this->salidaService->obtener($id);

        if (!$salida) {
            return $this->errorResponse('Salida no encontrada', 404);
        }

        $this->authorize('view', $salida);
        $this->assertEmpresa($salida);

        return $this->successResponse(SalidaInventarioResource::make($salida)->resolve());
    }

    /**
     * PUT /api/salidas-inventario/{id}
     * Actualizar salida de inventario
     */
    #[OA\Put(
        path: '/api/salidas-inventario/{id}',
        summary: 'Actualizar salida de inventario',
        description: 'Modifica una salida existente. Solo permitido si estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateSalidaInventarioRequest $request, int $id): JsonResponse
    {
        $salida = $this->salidaService->obtener($id);

        if (!$salida) {
            return $this->errorResponse('Salida no encontrada', 404);
        }

        $this->authorize('update', $salida);
        $this->assertEmpresa($salida);

        if ($salida->estado === 'Procesada') {
            return $this->errorResponse('No se puede modificar una salida ya procesada', 422);
        }

        $salida = $this->salidaService->actualizar($salida, $request->validated());

        return $this->successResponse(
            SalidaInventarioResource::make($salida)->resolve(),
            'Salida actualizada exitosamente'
        );
    }

    /**
     * DELETE /api/salidas-inventario/{id}
     * Eliminar salida de inventario
     */
    #[OA\Delete(
        path: '/api/salidas-inventario/{id}',
        summary: 'Eliminar salida de inventario',
        description: 'Elimina una salida. Solo permitido si estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $salida = $this->salidaService->obtener($id);

        if (!$salida) {
            return $this->errorResponse('Salida no encontrada', 404);
        }

        $this->authorize('delete', $salida);
        $this->assertEmpresa($salida);

        if ($salida->estado === 'Procesada') {
            return $this->errorResponse('No se puede eliminar una salida ya procesada', 422);
        }

        $this->salidaService->eliminar($salida);

        return $this->deletedResponse('Salida eliminada exitosamente');
    }

    /**
     * POST /api/salidas-inventario/{id}/procesar
     * Procesar salida y actualizar stock
     */
    #[OA\Post(
        path: '/api/salidas-inventario/{id}/procesar',
        summary: 'Procesar salida de inventario',
        description: 'Procesa la salida y reduce el stock. Valida stock suficiente. Acción irreversible',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function procesar(int $id): JsonResponse
    {
        $salida = $this->salidaService->obtener($id);

        if (!$salida) {
            return $this->errorResponse('Salida no encontrada', 404);
        }

        $this->authorize('update', $salida);
        $this->assertEmpresa($salida);

        $salida = $this->salidaService->procesar($salida);

        return $this->successResponse(
            SalidaInventarioResource::make($salida)->resolve(),
            'Salida procesada exitosamente, stock actualizado'
        );
    }
}
