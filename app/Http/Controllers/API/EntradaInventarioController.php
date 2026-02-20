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
        tags: ['Inventario - Entradas']
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
        tags: ['Inventario - Entradas']
    )]
    public function store(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', EntradaInventario::class);

        try {
            $entrada = $this->entradaService->crear($request->validated());

            return response()->json([
                'message' => 'Entrada de inventario creada exitosamente',
                'data' => EntradaInventarioResource::make($entrada)->resolve()
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al crear entrada de inventario',
                'error' => $e->getMessage()
            ], 422);
        }
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
        tags: ['Inventario - Entradas']
    )]
    public function show(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return response()->json(['message' => 'Entrada no encontrada'], 404);
        }

        $this->authorize('view', $entrada);
        $this->assertEmpresa($entrada);

        return response()->json(EntradaInventarioResource::make($entrada)->resolve());
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
        tags: ['Inventario - Entradas']
    )]
    public function update(UpdateEntradaInventarioRequest $request, int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return response()->json(['message' => 'Entrada no encontrada'], 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        if ($entrada->estado !== 'pendiente') {
            return response()->json(['message' => 'No se puede modificar una entrada procesada o anulada'], 422);
        }

        try {
            $entrada = $this->entradaService->actualizar($entrada, $request->validated());

            return response()->json([
                'message' => 'Entrada actualizada exitosamente',
                'data' => EntradaInventarioResource::make($entrada)->resolve()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar entrada',
                'error' => $e->getMessage()
            ], 422);
        }
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
        tags: ['Inventario - Entradas']
    )]
    public function destroy(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return response()->json(['message' => 'Entrada no encontrada'], 404);
        }

        $this->authorize('delete', $entrada);
        $this->assertEmpresa($entrada);

        if ($entrada->estado !== 'pendiente') {
            return response()->json(['message' => 'No se puede eliminar una entrada procesada o anulada'], 422);
        }

        try {
            $this->entradaService->eliminar($entrada);

            return response()->json(['message' => 'Entrada eliminada exitosamente']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al eliminar entrada',
                'error' => $e->getMessage()
            ], 500);
        }
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
        tags: ['Inventario - Entradas']
    )]
    public function procesar(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return response()->json(['message' => 'Entrada no encontrada'], 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        try {
            $entrada = $this->entradaService->procesar($entrada);

            return response()->json([
                'message' => 'Entrada procesada exitosamente, stock actualizado',
                'data' => EntradaInventarioResource::make($entrada)->resolve()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al procesar entrada',
                'error' => $e->getMessage()
            ], 422);
        }
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
        tags: ['Inventario - Entradas']
    )]
    public function cancelar(int $id): JsonResponse
    {
        $entrada = $this->entradaService->obtener($id);

        if (!$entrada) {
            return response()->json(['message' => 'Entrada no encontrada'], 404);
        }

        $this->authorize('update', $entrada);
        $this->assertEmpresa($entrada);

        try {
            $entrada = $this->entradaService->cancelar($entrada);

            return response()->json([
                'message' => 'Entrada cancelada exitosamente',
                'data' => EntradaInventarioResource::make($entrada)->resolve()
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al cancelar entrada',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
