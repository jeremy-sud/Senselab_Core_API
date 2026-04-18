<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Http\Requests\UpdateEntradaInventarioRequest;
use App\Http\Resources\EntradaInventarioResource;
use App\Models\EntradaInventario;
use App\Services\EntradaInventarioService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * EntradaInventarioController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado usando Service Layer Pattern
 * Reducción de líneas: 722 → ~220 (-70%)
 *
 * Refactorización completada: 13 de febrero de 2026
 */
class EntradaInventarioController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private EntradaInventarioService $entradaService) {}

    /**
     * GET /api/entradas-inventario
     * Listar entradas con paginación y filtros opcionales
     */
    #[OA\Get(
        path: '/api/entradas-inventario',
        summary: 'Listar entradas de inventario',
        description: 'Obtiene listado paginado de entradas con filtros opcionales por proveedor, bodega y fechas',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EntradaInventario::class);
        $this->resolveEmpresaOrFail($request->input('empresa_id'));

        $perPage = (int) $request->input('per_page', 15);

        $entradas = match (true) {
            $request->filled('proveedor_id')
                => $this->entradaService->porProveedor((int) $request->input('proveedor_id'), $perPage),

            $request->filled('bodega_id')
                => $this->entradaService->porAlmacen((int) $request->input('bodega_id'), $perPage),

            $request->filled(['fecha_inicio', 'fecha_fin'])
                => $this->entradaService->entreFechas(
                    new \DateTime($request->input('fecha_inicio')),
                    new \DateTime($request->input('fecha_fin')),
                    $perPage
                ),

            default => $this->entradaService->listar($perPage)
        };

        return EntradaInventarioResource::collection($entradas);
    }

    /**
     * POST /api/entradas-inventario
     * Crear nueva entrada de inventario
     */
    #[OA\Post(
        path: '/api/entradas-inventario',
        summary: 'Crear entrada de inventario',
        description: 'Registra una nueva entrada de inventario en estado Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', EntradaInventario::class);

        $entrada = $this->entradaService->crear($request->validated());

        return $this->createdResponse(
            EntradaInventarioResource::make($entrada)->resolve(),
            'Entrada de inventario creada exitosamente'
        );
    }

    /**
     * GET /api/entradas-inventario/{id}
     * Obtener detalle de una entrada
     */
    #[OA\Get(
        path: '/api/entradas-inventario/{id}',
        summary: 'Obtener entrada de inventario',
        description: 'Retorna los datos de una entrada específica con sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return $this->errorResponse('Entrada no encontrada', 404);
        }

        $this->authorize('view', $entrada);
        $this->assertEmpresa($entrada);

        return $this->successResponse(EntradaInventarioResource::make($entrada)->resolve());
    }

    /**
     * PUT /api/entradas-inventario/{id}
     * Actualizar entrada de inventario
     */
    #[OA\Put(
        path: '/api/entradas-inventario/{id}',
        summary: 'Actualizar entrada de inventario',
        description: 'Modifica una entrada existente. Solo permitido si estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateEntradaInventarioRequest $request, int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return $this->errorResponse('Entrada no encontrada', 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        if ($entrada->estado !== 'pendiente') {
            return $this->errorResponse('No se puede modificar una entrada procesada o anulada', 422);
        }

        $entrada = $this->entradaService->actualizar($entrada, $request->validated());

        return $this->successResponse(
            EntradaInventarioResource::make($entrada)->resolve(),
            'Entrada actualizada exitosamente'
        );
    }

    /**
     * DELETE /api/entradas-inventario/{id}
     * Eliminar entrada de inventario
     */
    #[OA\Delete(
        path: '/api/entradas-inventario/{id}',
        summary: 'Eliminar entrada de inventario',
        description: 'Elimina una entrada. Solo permitido si estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso eliminado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return $this->errorResponse('Entrada no encontrada', 404);
        }

        $this->authorize('delete', $entrada);
        $this->assertEmpresa($entrada);

        if ($entrada->estado !== 'pendiente') {
            return $this->errorResponse('No se puede eliminar una entrada procesada o anulada', 422);
        }

        $this->entradaService->eliminar($entrada);

        return $this->deletedResponse('Entrada eliminada exitosamente');
    }

    /**
     * PUT /api/entradas-inventario/{id}/procesar
     * Procesar entrada y actualizar stock
     */
    #[OA\Put(
        path: '/api/entradas-inventario/{id}/procesar',
        summary: 'Procesar entrada de inventario',
        description: 'Procesa la entrada y actualiza el stock. Acción irreversible',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 200, description: 'Recurso actualizado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Recurso no encontrado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function procesar(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return $this->errorResponse('Entrada no encontrada', 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        $entrada = $this->entradaService->procesar($entrada);

        return $this->successResponse(
            EntradaInventarioResource::make($entrada)->resolve(),
            'Entrada procesada exitosamente, stock actualizado'
        );
    }

    /**
     * POST /api/entradas-inventario/{id}/cancelar
     * Cancelar entrada de inventario
     */
    #[OA\Post(
        path: '/api/entradas-inventario/{id}/cancelar',
        summary: 'Cancelar entrada de inventario',
        description: 'Cambia el estado a Cancelada. Solo para entradas Pendientes',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function cancelar(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return $this->errorResponse('Entrada no encontrada', 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        $entrada = $this->entradaService->cancelar($entrada);

        return $this->successResponse(
            EntradaInventarioResource::make($entrada)->resolve(),
            'Entrada cancelada exitosamente'
        );
    }
}
