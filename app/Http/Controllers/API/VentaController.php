<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\DTOs\API\VentaCreateDTO;
use App\Services\VentaService;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Resources\VentaResource;
use App\Traits\HasEmpresaContext;
use OpenApi\Attributes as OA;

/**
 * VentaController - Versión Refactorizada (FASE 4.2)
 * 
 * Controlador simplificado usando Service Layer Pattern
 * Delegación: Validación (FormRequest) → DTO → Service → Response
 * 
 * Reducción de líneas: 818 → 240 (-71%)
 * Complejidad ciclomática: Reducida significativamente
 * Testabilidad: Mejorada por inyección de dependencias
 * 
 * Refactorización completada: 12 de febrero de 2026
 */
class VentaController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private VentaService $ventaService) {}

    /**
     * GET /api/ventas
     * Listar ventas con paginación y filtros opcionales
     */
    #[OA\Get(
        path: '/api/ventas',
        summary: 'Listar ventas',
        description: 'Obtiene un listado paginado de ventas con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Ventas']
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Venta::class);
        $this->resolveEmpresaOrFail($request->input('empresa_id'));

        $perPage = (int) $request->input('per_page', 15);

        $ventas = match (true) {
            $request->filled('cliente_id')
                => $this->ventaService->porCliente((int) $request->input('cliente_id'), $perPage),
            
            $request->filled(['fecha_inicio', 'fecha_fin'])
                => $this->ventaService->entreFechas(
                    new \DateTime($request->input('fecha_inicio')),
                    new \DateTime($request->input('fecha_fin')),
                    $perPage
                ),
            
            default => $this->ventaService->listar($perPage)
        };

        return VentaResource::collection($ventas);
    }

    /**
     * POST /api/ventas
     * Crear nueva venta con cálculo automático de totales
     */
    #[OA\Post(
        path: '/api/ventas',
        summary: 'Crear una nueva venta',
        description: 'Registra una nueva venta con detalles y calcula totales automáticamente',
        security: [['sanctum' => []]],
        tags: ['Ventas']
    )]
    public function store(StoreVentaRequest $request): JsonResponse
    {
        $this->authorize('create', Venta::class);

        try {
            $dto = VentaCreateDTO::fromRequest($request);
            $venta = $this->ventaService->crear($dto);

            return response()->json([
                'data' => VentaResource::make($venta)->resolve()
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al crear venta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 422);
        }
    }

    /**
     * GET /api/ventas/{id}
     * Obtener detalle completo de una venta
     */
    #[OA\Get(
        path: '/api/ventas/{id}',
        summary: 'Obtener una venta específica',
        description: 'Obtiene los detalles completos de una venta incluyendo detalles de línea',
        security: [['sanctum' => []]],
        tags: ['Ventas']
    )]
    public function show(int $id): JsonResponse
    {
        $venta = $this->ventaService->obtener($id);

        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        $this->authorize('view', $venta);
        $this->assertEmpresa($venta);

        return response()->json(VentaResource::make($venta)->resolve());
    }

    /**
     * PUT /api/ventas/{id}
     * Actualizar observaciones y estado de venta
     */
    #[OA\Put(
        path: '/api/ventas/{id}',
        summary: 'Actualizar una venta',
        description: 'Actualiza observaciones y estado de venta',
        security: [['sanctum' => []]],
        tags: ['Ventas']
    )]
    public function update(UpdateVentaRequest $request, int $id): JsonResponse
    {
        $venta = $this->ventaService->obtener($id);

        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        $this->authorize('update', $venta);
        $this->assertEmpresa($venta);

        try {
            if ($request->filled('estado_venta')) {
                $venta = $this->ventaService->cambiarEstado(
                    $venta,
                    $request->input('estado_venta')
                );
            }

            if ($request->filled('observaciones')) {
                $venta->update(['observaciones' => $request->input('observaciones')]);
            }

            return response()->json(VentaResource::make($venta->fresh())->resolve());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al actualizar venta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 422);
        }
    }

    /**
     * DELETE /api/ventas/{id}
     * Anular una venta
     */
    #[OA\Delete(
        path: '/api/ventas/{id}',
        summary: 'Anular una venta',
        description: 'Marca la venta como anulada y no eliminable',
        security: [['sanctum' => []]],
        tags: ['Ventas']
    )]
    public function destroy(int $id): JsonResponse
    {
        $venta = $this->ventaService->obtener($id);

        if (!$venta) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        $this->authorize('delete', $venta);
        $this->assertEmpresa($venta);

        try {
            $this->ventaService->anular($venta);

            return response()->json(['message' => 'Venta anulada exitosamente']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error al anular venta',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * GET /api/ventas/reporte/total-periodo
     * Total de ventas en período
     */
    #[OA\Get(
        path: '/api/ventas/reporte/total-periodo',
        summary: 'Obtener total de ventas en período',
        description: 'Calcula el monto total de ventas en un rango de fechas',
        security: [['sanctum' => []]],
        tags: ['Ventas', 'Reportes']
    )]
    public function totalPeriodo(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        $inicio = new \DateTime($request->input('fecha_inicio'));
        $fin = new \DateTime($request->input('fecha_fin'));

        $total = $this->ventaService->totalEnPeriodo($inicio, $fin);

        return response()->json([
            'total' => $total,
            'periodo' => [
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
            ],
        ]);
    }
}
