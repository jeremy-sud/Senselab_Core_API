<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\OrdenCompra;
use App\Models\DetalleOrdenCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreOrdenCompraRequest;
use App\Http\Requests\UpdateOrdenCompraRequest;
use App\Http\Resources\OrdenCompraResource;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrdenCompraController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int,string> */
    /** @var array<int, string> */
    protected array $cacheTags = ['ordenes-compra', 'transacciones'];
    protected int $cacheTTL = 900; // 15 minutos
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/ordenes-compra',
        summary: 'Listar órdenes de compra',
        description: 'Obtiene listado paginado de órdenes de compra con filtros',
        security: [['sanctum' => []]],
        tags: ['Órdenes de Compra'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'empresa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'proveedor_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['borrador', 'enviada', 'confirmada', 'recibida_parcial', 'recibida_completa', 'cancelada'])),
            new OA\Parameter(name: 'pendientes', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'activas', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrdenCompra'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OrdenCompra::class);

        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            $proveedorId = $request->input('proveedor_id');
            $estado = $request->input('estado');

            $ordenes = $this->cacheQueryIfEnabled(
                $this->getCacheKey('index', $request->all()),
                function() use ($request, $perPage, $empresaId, $proveedorId, $estado) {
                    $query = OrdenCompra::with([
                        'empresa',
                        'proveedor',
                        'usuario'
                    ]);

                    if ($empresaId) {
                        $query->porEmpresa($empresaId);
                    }

                    if ($proveedorId) {
                        $query->porProveedor($proveedorId);
                    }

                    if ($estado) {
                        $query->where('estado', $estado);
                    }

                    if ($request->boolean('pendientes')) {
                        $query->pendientes();
                    }

                    if ($request->boolean('activas')) {
                        $query->activas();
                    }

                    return $query->orderBy('id', 'desc')->paginate($perPage);
                }
            );

            return OrdenCompraResource::collection($ordenes);
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreOrdenCompraRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/ordenes-compra',
        summary: 'Crear orden de compra',
        description: 'Crea una nueva orden de compra con sus detalles. Genera número automáticamente',
        security: [['sanctum' => []]],
        tags: ['Órdenes de Compra'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'proveedor_id', 'fecha_orden', 'detalles'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer'),
                    new OA\Property(property: 'proveedor_id', type: 'integer'),
                    new OA\Property(property: 'fecha_orden', type: 'string', format: 'date'),
                    new OA\Property(property: 'fecha_entrega_esperada', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'moneda', type: 'string', enum: ['CRC', 'USD'], example: 'CRC'),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'producto_id', type: 'integer'),
                                new OA\Property(property: 'cantidad', type: 'number'),
                                new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal'),
                                new OA\Property(property: 'descuento', type: 'number', format: 'decimal', nullable: true),
                                new OA\Property(property: 'descripcion', type: 'string', nullable: true)
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Orden creada', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/OrdenCompra')]))
        ]
    )]
    public function store(StoreOrdenCompraRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', OrdenCompra::class);

        try {
            DB::beginTransaction();

            try {
                // Crear orden de compra
                $ordenData = $request->except('detalles');
                $ordenData['numero_orden'] = $this->generarNumeroOrden($request->empresa_id);

                $orden = OrdenCompra::create($ordenData);

                // Crear detalles
                $montoSubtotal = 0;
                $montoImpuestos = 0;

                foreach ($request->detalles as $detalle) {
                    $cantidad = $detalle['cantidad'];
                    $precioUnitario = $detalle['precio_unitario'];
                    $descuento = $detalle['descuento'] ?? 0;

                    $subtotal = ($cantidad * $precioUnitario) - $descuento;
                    $montoSubtotal += $subtotal;

                    DetalleOrdenCompra::create([
                        'orden_compra_id' => $orden->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'subtotal' => $subtotal,
                        'descripcion' => $detalle['descripcion'] ?? null,
                    ]);
                }

                // Actualizar totales
                $orden->update([
                    'subtotal' => $montoSubtotal,
                    'impuesto_total' => $montoImpuestos,
                    'total_orden' => $montoSubtotal + $montoImpuestos,
                ]);

                $this->flushCache();
                DB::commit();

                $orden->load(['proveedor', 'detalles.producto']);

                return (new OrdenCompraResource($orden))
                    ->additional(['message' => 'Orden de compra creada exitosamente'])
                    ->response()
                    ->setStatusCode(201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear orden de compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return OrdenCompraResource
     */
    #[OA\Get(
        path: '/api/ordenes-compra/{id}',
        summary: 'Obtener orden de compra',
        description: 'Detalles completos de una orden con líneas, pagos y entradas de inventario',
        security: [['sanctum' => []]],
        tags: ['Órdenes de Compra'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Orden encontrada'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(int $id): OrdenCompraResource
    {
        try {
            $orden = OrdenCompra::with([
                'empresa',
                'proveedor',
                'usuario',
                'detalles.producto',
                'pagos',
                'entradasInventario'
            ])->findOrFail($id);
            $this->authorize('view', $orden);

            // Calcular saldo pendiente
            $orden->saldo_pendiente = $orden->calcularSaldoPendiente();

            return new OrdenCompraResource($orden);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Orden de compra no encontrada');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateOrdenCompraRequest $request
     * @param int $id
     * @return OrdenCompraResource
     */
    #[OA\Put(
        path: '/api/ordenes-compra/{id}',
        summary: 'Actualizar orden de compra',
        security: [['sanctum' => []]],
        tags: ['Órdenes de Compra'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizada')]
    )]
    public function update(UpdateOrdenCompraRequest $request, int $id): OrdenCompraResource
    {
        try {
            $orden = OrdenCompra::with([
                'proveedor',
                'detalles.producto',
                'empresa'
            ])->findOrFail($id);
            $this->authorize('update', $orden);

            $orden->update($request->validated());

            return (new OrdenCompraResource($orden))
                ->additional(['message' => 'Orden de compra actualizada exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Orden de compra no encontrada');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/ordenes-compra/{id}',
        summary: 'Eliminar orden de compra',
        description: 'Soft delete. Solo permite eliminar en estado borrador',
        security: [['sanctum' => []]],
        tags: ['Órdenes de Compra'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminada'),
            new OA\Response(response: 422, description: 'Solo se pueden eliminar en estado borrador')
        ]
    )]
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $orden = OrdenCompra::with(['detalles'])->findOrFail($id);
        $this->authorize('delete', $orden);

        // Solo permitir eliminar en estado borrador
        if ($orden->estado !== 'borrador') {
            return response()->json([
                'message' => 'Solo se pueden eliminar órdenes en estado borrador'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Eliminar detalles
            $orden->detalles()->delete();

            // Soft delete de la orden
            $orden->update([
                'activo' => false,
                'eliminado' => true
            ]);

            $this->flushCache();
            DB::commit();

            return response()->json([
                'message' => 'Orden de compra eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            throw $e;
        }
    }

    /**
     * Generar número de orden único
     *
     * @param int $empresaId
     * @return string
     */
    private function generarNumeroOrden(int $empresaId): string
    {
        $ultimaOrden = OrdenCompra::where('empresa_id', $empresaId)
                                  ->orderBy('id', 'desc')
                                  ->first();

        $numero = $ultimaOrden ? (int)substr($ultimaOrden->numero_orden, -6) + 1 : 1;

        return 'OC-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}
