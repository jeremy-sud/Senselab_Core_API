<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaPorCobrarRequest;
use App\Http\Requests\UpdateCuentaPorCobrarRequest;
use App\Http\Resources\CuentaPorCobrarResource;
use App\Models\CuentaPorCobrar;
use App\Services\CuentaPorCobrarService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    use HasEmpresaContext;

    public function __construct(private CuentaPorCobrarService $service) {}
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

        $cuentas = $this->service->listar(
            $this->getEmpresaId(),
            $request->only(['estado', 'cliente_id', 'vencidas', 'fecha_desde', 'fecha_hasta', 'sort_by', 'sort_order']),
            $request->integer('per_page', 15)
        );

        return CuentaPorCobrarResource::collection($cuentas);
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

        $data = $request->validated();
        $data['empresa_id'] = $this->getEmpresaId();
        $cuenta = $this->service->crear($data);

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
        $cuenta = $this->service->obtener($this->getEmpresaId(), $id);
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
        $cuenta = $this->service->obtener($this->getEmpresaId(), $id);
        $this->authorize('update', $cuenta);

        $cuenta = $this->service->actualizar($cuenta, $request->validated());

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
        $cuenta = $this->service->obtener($this->getEmpresaId(), $id);
        $this->authorize('delete', $cuenta);

        $this->service->eliminar($cuenta);

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
        $data = $this->service->vencidas($this->getEmpresaId());

        return response()->json([
            'success' => true,
            'data' => [
                'total_vencido' => $data['total_vencido'],
                'cantidad_vencidas' => $data['cantidad_vencidas'],
                'cuentas' => CuentaPorCobrarResource::collection($data['cuentas'])
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
        $data = $this->service->resumen($this->getEmpresaId());

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
