<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaPorPagarRequest;
use App\Http\Requests\UpdateCuentaPorPagarRequest;
use App\Http\Resources\CuentaPorPagarResource;
use App\Models\CuentaPorPagar;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Cuentas por Pagar
 *
 * Gestiona las cuentas por pagar de la empresa a proveedores y acreedores.
 * Incluye control de saldos, vencimientos y estados de pago.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorPagarController extends Controller
{
    use HasCacheableQueries;

    /**
     * Tags para invalidación de cache
     * @var array<string>
     */
    protected array $cacheTags = ['cuentas-por-pagar', 'finanzas'];

    /**
     * TTL del cache en segundos (30 minutos)
     * Datos semi-dinámicos: saldos actualizan con pagos
     * @var int
     */
    protected int $cacheTTL = 1800;
    /**
     * Listar todas las cuentas por pagar de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/cuentas-por-pagar',
        summary: 'Listar cuentas por pagar',
        description: 'Obtiene listado paginado de cuentas por pagar con filtros de estado, proveedor y fechas',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Pendiente', 'Pagada Parcialmente', 'Pagada', 'Vencida', 'Cancelada'])),
            new OA\Parameter(name: 'proveedor_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'vencidas', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CuentaPorPagar'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CuentaPorPagar::class);

        $empresaId = $request->user()->empresa_id;
        
        return $this->cacheQueryIfEnabled(function () use ($request, $empresaId) {
            $query = CuentaPorPagar::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['proveedor', 'ordenCompra', 'empresa']);

            // Filtros opcionales
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('proveedor_id')) {
                $query->where('proveedor_id', $request->proveedor_id);
            }

            if ($request->filled('vencidas')) {
                $query->where('fecha_vencimiento', '<', now())
                    ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente']);
            }

            if ($request->filled('desde') && $request->filled('hasta')) {
                $query->whereBetween('fecha_emision', [$request->desde, $request->hasta]);
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'fecha_vencimiento'), $request->get('sort_order', 'asc'));

            $cuentas = $query->paginate($request->get('per_page', 15));

            return CuentaPorPagarResource::collection($cuentas);
        }, [
            'estado' => $request->input('estado'),
            'proveedor_id' => $request->input('proveedor_id'),
            'vencidas' => $request->input('vencidas'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'sort_by' => $request->input('sort_by'),
            'sort_order' => $request->input('sort_order'),
            'per_page' => $request->input('per_page')
        ]);
    }

    /**
     * Crear una nueva cuenta por pagar
     *
     * @param StoreCuentaPorPagarRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/cuentas-por-pagar',
        summary: 'Crear cuenta por pagar',
        description: 'Crea nueva cuenta por pagar asociada a proveedor u orden de compra',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['proveedor_id', 'numero_documento', 'fecha_emision', 'fecha_vencimiento', 'monto_total'],
                properties: [
                    new OA\Property(property: 'proveedor_id', type: 'integer', example: 3),
                    new OA\Property(property: 'orden_compra_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'numero_documento', type: 'string', example: 'PROV-001-2024'),
                    new OA\Property(property: 'fecha_emision', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2024-02-15'),
                    new OA\Property(property: 'fecha_recepcion_documento', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', example: 250000.00),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cuenta creada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CuentaPorPagar')]))
        ]
    )]
    public function store(StoreCuentaPorPagarRequest $request): JsonResponse
    {
        $this->authorize('create', CuentaPorPagar::class);

        $validated = $request->validated();
        $validated['empresa_id'] = $request->user()->empresa_id;

        // Fecha de recepción por defecto es hoy si no se proporciona
        if (!isset($validated['fecha_recepcion_documento'])) {
            $validated['fecha_recepcion_documento'] = now()->toDateString();
        }

        $cuenta = CuentaPorPagar::create($validated);
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);
        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar creada exitosamente',
            'data' => new CuentaPorPagarResource($cuenta)
        ], 201);
    }

    /**
     * Mostrar una cuenta por pagar específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-pagar/{id}',
        summary: 'Obtener cuenta por pagar',
        description: 'Detalles completos con proveedor, orden de compra y pagos',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Cuenta encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['proveedor', 'ordenCompra', 'empresa'])
            ->firstOrFail();
        $this->authorize('view', $cuenta);

        return response()->json([
            'success' => true,
            'data' => new CuentaPorPagarResource($cuenta)
        ]);
    }

    /**
     * Actualizar una cuenta por pagar
     *
     * @param UpdateCuentaPorPagarRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/cuentas-por-pagar/{id}',
        summary: 'Actualizar cuenta por pagar',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateCuentaPorPagarRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('update', $cuenta);

        $cuenta->update($request->validated());
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);
        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar actualizada exitosamente',
            'data' => new CuentaPorPagarResource($cuenta)
        ]);
    }

    /**
     * Eliminar (soft delete) una cuenta por pagar
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/cuentas-por-pagar/{id}',
        summary: 'Eliminar cuenta por pagar',
        description: 'Soft delete. Valida que no tenga pagos registrados',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminada'),
            new OA\Response(response: 422, description: 'No se puede eliminar con pagos registrados')
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('delete', $cuenta);

        // Validar que no tenga pagos registrados
        if ($cuenta->monto_pagado > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta por pagar que ya tiene pagos registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);
        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar eliminada exitosamente'
        ]);
    }

    /**
     * Obtener resumen de cuentas por pagar vencidas
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-pagar/vencidas',
        summary: 'Listar cuentas vencidas',
        description: 'Obtiene cuentas con fecha_vencimiento pasada y estado Pendiente/Pagada Parcialmente',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de vencidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_vencido', type: 'number', example: 180000),
                        new OA\Property(property: 'cantidad_vencidas', type: 'integer', example: 5),
                        new OA\Property(property: 'cuentas', type: 'array', items: new OA\Items(ref: '#/components/schemas/CuentaPorPagar'))
                    ]
                )
            )
        ]
    )]
    public function vencidas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $vencidas = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('fecha_vencimiento', '<', now())
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente'])
            ->with(['proveedor'])
            ->get();

        $totalVencido = $vencidas->sum('monto_pendiente');
        $cantidadVencidas = $vencidas->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_vencido' => $totalVencido,
                'cantidad_vencidas' => $cantidadVencidas,
                'cuentas' => CuentaPorPagarResource::collection($vencidas)
            ]
        ]);
    }

    /**
     * Obtener resumen de cuentas por pagar por estado
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-pagar/resumen',
        summary: 'Resumen de cuentas por pagar',
        description: 'Totales agregados por estado para dashboard',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Pagar'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_por_pagar', type: 'number', example: 750000),
                        new OA\Property(property: 'total_vencido', type: 'number', example: 180000),
                        new OA\Property(property: 'por_estado', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function resumen(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_pendiente) as total_saldo'))
            ->groupBy('estado')
            ->get();

        $totalGeneral = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->sum('monto_pendiente');

        return response()->json([
            'success' => true,
            'data' => [
                'por_estado' => $resumen,
                'total_general' => $totalGeneral
            ]
        ]);
    }
}
