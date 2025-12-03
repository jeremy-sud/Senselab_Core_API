<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaPorCobrarRequest;
use App\Http\Requests\UpdateCuentaPorCobrarRequest;
use App\Http\Resources\CuentaPorCobrarResource;
use App\Models\CuentaPorCobrar;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Cuentas por Cobrar
 *
 * Gestiona las cuentas por cobrar de la empresa, generadas por ventas a crédito.
 * Incluye control de saldos, vencimientos y estados de pago.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorCobrarController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /**
     * Tags para invalidación de cache
     * @var array<string>
     */
    protected array $cacheTags = ['cuentas-por-cobrar', 'finanzas'];

    /**
     * TTL del cache en segundos (30 minutos)
     * Datos semi-dinámicos: saldos actualizan con pagos
     * @var int
     */
    protected int $cacheTTL = 1800;
    /**
     * Listar todas las cuentas por cobrar de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/cuentas-por-cobrar',
        summary: 'Listar cuentas por cobrar',
        description: 'Obtiene listado paginado de cuentas por cobrar con filtros de estado, cliente y fechas',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Pendiente', 'Pagada Parcialmente', 'Pagada', 'Vencida', 'Cancelada'])),
            new OA\Parameter(name: 'cliente_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'vencidas', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CuentaPorCobrar'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CuentaPorCobrar::class);

        $empresaId = $this->getEmpresaId();

        $cacheKey = $this->getCacheKey('index', [
            'estado' => $request->estado,
            'cliente_id' => $request->cliente_id,
            'vencidas' => $request->vencidas,
            'desde' => $request->desde,
            'hasta' => $request->hasta
        ]);

        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId) {
            $query = CuentaPorCobrar::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['cliente', 'venta', 'empresa']);

            // Filtros opcionales
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
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

            return CuentaPorCobrarResource::collection($cuentas);
        });
    }

    /**
     * Crear una nueva cuenta por cobrar
     *
     * @param StoreCuentaPorCobrarRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/cuentas-por-cobrar',
        summary: 'Crear cuenta por cobrar',
        description: 'Crea nueva cuenta por cobrar asociada a venta',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['venta_id', 'cliente_id', 'numero_documento', 'fecha_emision', 'fecha_vencimiento', 'monto_total'],
                properties: [
                    new OA\Property(property: 'venta_id', type: 'integer', example: 1),
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 5),
                    new OA\Property(property: 'numero_documento', type: 'string', example: 'FAC-2024-001'),
                    new OA\Property(property: 'fecha_emision', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2024-02-15'),
                    new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', example: 150000.00),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cuenta creada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/CuentaPorCobrar')]))
        ]
    )]
    public function store(StoreCuentaPorCobrarRequest $request): CuentaPorCobrarResource|JsonResponse
    {
        $this->authorize('create', CuentaPorCobrar::class);

        $validated = $request->validated();
        $validated['empresa_id'] = $this->getEmpresaId();

        $cuenta = CuentaPorCobrar::create($validated);
        $cuenta->load(['cliente', 'venta', 'empresa']);
        $this->flushCache();

        return (new CuentaPorCobrarResource($cuenta))
            ->additional([
                'success' => true,
                'message' => 'Cuenta por cobrar creada exitosamente'
            ]);
    }

    /**
     * Mostrar una cuenta por cobrar específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-cobrar/{id}',
        summary: 'Obtener cuenta por cobrar',
        description: 'Detalles completos con cliente, venta y pagos',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Cuenta encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(int $id, Request $request): CuentaPorCobrarResource|JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['cliente', 'venta', 'empresa'])
            ->firstOrFail();
        $this->authorize('view', $cuenta);

        return (new CuentaPorCobrarResource($cuenta))
            ->additional(['success' => true]);
    }

    /**
     * Actualizar una cuenta por cobrar
     *
     * @param UpdateCuentaPorCobrarRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/cuentas-por-cobrar/{id}',
        summary: 'Actualizar cuenta por cobrar',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateCuentaPorCobrarRequest $request, int $id): CuentaPorCobrarResource|JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('update', $cuenta);

        $cuenta->update($request->validated());
        $cuenta->load(['cliente', 'venta', 'empresa']);
        $this->flushCache();

        return (new CuentaPorCobrarResource($cuenta))
            ->additional([
                'success' => true,
                'message' => 'Cuenta por cobrar actualizada exitosamente'
            ]);
    }

    /**
     * Eliminar (soft delete) una cuenta por cobrar
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/cuentas-por-cobrar/{id}',
        summary: 'Eliminar cuenta por cobrar',
        description: 'Soft delete. Valida que no tenga pagos registrados',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminada'),
            new OA\Response(response: 422, description: 'No se puede eliminar con pagos registrados')
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        $this->authorize('delete', $cuenta);

        // Validar que no tenga pagos registrados
        if ($cuenta->monto_pagado > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta por cobrar que ya tiene pagos registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);
        $this->flushCache();

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por cobrar eliminada exitosamente'
        ]);
    }

    /**
     * Obtener resumen de cuentas por cobrar vencidas
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-cobrar/vencidas',
        summary: 'Listar cuentas vencidas',
        description: 'Obtiene cuentas con fecha_vencimiento pasada y estado Pendiente/Pagada Parcialmente',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de vencidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_vencido', type: 'number', example: 125000),
                        new OA\Property(property: 'cantidad_vencidas', type: 'integer', example: 3),
                        new OA\Property(property: 'cuentas', type: 'array', items: new OA\Items(ref: '#/components/schemas/CuentaPorCobrar'))
                    ]
                )
            )
        ]
    )]
    public function vencidas(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $vencidas = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('fecha_vencimiento', '<', now())
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente'])
            ->with(['cliente'])
            ->get();

        $totalVencido = $vencidas->sum('monto_pendiente');
        $cantidadVencidas = $vencidas->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_vencido' => $totalVencido,
                'cantidad_vencidas' => $cantidadVencidas,
                'cuentas' => CuentaPorCobrarResource::collection($vencidas)
            ]
        ]);
    }

    /**
     * Obtener resumen de cuentas por cobrar por estado
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/cuentas-por-cobrar/resumen',
        summary: 'Resumen de cuentas por cobrar',
        description: 'Totales agregados por estado para dashboard',
        security: [['sanctum' => []]],
        tags: ['Cuentas por Cobrar'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_por_cobrar', type: 'number', example: 500000),
                        new OA\Property(property: 'total_vencido', type: 'number', example: 125000),
                        new OA\Property(property: 'por_estado', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function resumen(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $resumen = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_pendiente) as total_saldo'))
            ->groupBy('estado')
            ->get();

        $totalGeneral = CuentaPorCobrar::where('empresa_id', $empresaId)
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
