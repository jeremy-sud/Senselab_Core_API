<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\InventarioProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Resources\VentaResource;
use App\Traits\HasCacheableQueries;
use App\Jobs\GeneratePdfReportJob;
use OpenApi\Attributes as OA;

class VentaController extends Controller
{
    use HasCacheableQueries;
    
    /** @var array<int,string> */
    protected array $cacheTags = ['ventas', 'transacciones'];
    protected int $cacheTTL = 600; // 10 minutos (datos muy dinámicos)
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/ventas',
        summary: 'Listar ventas',
        description: 'Obtiene un listado paginado de ventas con filtros por empresa, sucursal, cliente y rango de fechas',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, example: 15)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Número de página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'empresa_id',
                description: 'Filtrar por empresa',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'sucursal_id',
                description: 'Filtrar por sucursal',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'cliente_id',
                description: 'Filtrar por cliente',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 5)
            ),
            new OA\Parameter(
                name: 'fecha_inicio',
                description: 'Fecha de inicio del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
            ),
            new OA\Parameter(
                name: 'fecha_fin',
                description: 'Fecha de fin del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de ventas obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Venta')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 142)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener ventas'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Venta::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            $sucursalId = $request->input('sucursal_id');
            $clienteId = $request->input('cliente_id');
            $fechaInicio = $request->input('fecha_inicio');
            $fechaFin = $request->input('fecha_fin');
            
            $ventas = $this->cacheQueryIfEnabled(
                $this->getCacheKey('index', $request->all()),
                function() use ($request, $perPage, $empresaId, $sucursalId, $clienteId, $fechaInicio, $fechaFin) {
                    $query = Venta::with([
                        'empresa',
                        'sucursal',
                        'cliente',
                        'usuario',
                        'formaPago'
                    ]);
                    
                    if ($empresaId) {
                        $query->where('empresa_id', $empresaId);
                    }
                    
                    if ($sucursalId) {
                        $query->where('sucursal_id', $sucursalId);
                    }
                    
                    if ($clienteId) {
                        $query->where('cliente_id', $clienteId);
                    }
                    
                    if ($fechaInicio && $fechaFin) {
                        $query->whereBetween('fecha_venta', [$fechaInicio, $fechaFin]);
                    }
                    
                    return $query->orderBy('id', 'desc')->paginate($perPage);
                }
            );
            
            return VentaResource::collection($ventas)->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreVentaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/ventas',
        summary: 'Crear una nueva venta',
        description: 'Registra una nueva venta con sus detalles de línea, calculando automáticamente subtotales, descuentos, impuestos y total',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'cliente_id', 'fecha_venta', 'tipo_comprobante', 'moneda', 'condicion_pago', 'detalles'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sucursal_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 5),
                    new OA\Property(property: 'usuario_id', type: 'integer', nullable: true, example: 3),
                    new OA\Property(property: 'fecha_venta', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00'),
                    new OA\Property(property: 'tipo_comprobante', type: 'string', enum: ['factura', 'tiquete', 'nota_credito', 'nota_debito'], example: 'factura'),
                    new OA\Property(property: 'moneda', type: 'string', enum: ['CRC', 'USD'], example: 'CRC'),
                    new OA\Property(property: 'condicion_pago', type: 'string', enum: ['contado', 'credito'], example: 'contado'),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 30),
                    new OA\Property(property: 'forma_pago_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true, example: 'Venta con descuento especial'),
                    new OA\Property(
                        property: 'detalles',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'producto_id', type: 'integer', example: 10),
                                new OA\Property(property: 'cantidad', type: 'number', example: 2),
                                new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', example: 50000.00),
                                new OA\Property(property: 'descuento', type: 'number', format: 'decimal', example: 5000.00),
                                new OA\Property(property: 'porcentaje_impuesto', type: 'number', format: 'decimal', example: 13),
                                new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Producto especial')
                            ],
                            type: 'object'
                        )
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Venta creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Venta'),
                        new OA\Property(property: 'message', type: 'string', example: 'Venta creada exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al crear venta'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function store(StoreVentaRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Venta::class);
        
        try {
            DB::beginTransaction();
            
            try {
                // Validar stock si se proporciona almacen_id
                if ($request->has('almacen_id')) {
                    foreach ($request->detalles as $detalle) {
                        $inventario = InventarioProducto::where('producto_id', $detalle['producto_id'])
                            ->where('almacen_id', $request->almacen_id)
                            ->first();

                        if (!$inventario || $inventario->stock_actual < $detalle['cantidad']) {
                            return response()->json([
                                'message' => 'Stock insuficiente',
                                'errors' => [
                                    'stock' => ["No hay suficiente stock para el producto ID {$detalle['producto_id']}"]
                                ]
                            ], 422);
                        }
                    }
                }

                // Crear venta
                $ventaData = $request->except('detalles');
                $ventaData['numero_comprobante'] = $this->generarNumeroComprobante($request->empresa_id, $request->tipo_comprobante);
                
                $venta = Venta::create($ventaData);
                
                // Crear detalles y calcular totales
                $montoSubtotal = 0;
                $montoImpuestos = 0;
                $montoDescuentos = 0;
                
                foreach ($request->detalles as $detalle) {
                    $cantidad = $detalle['cantidad'];
                    $precioUnitario = $detalle['precio_unitario'];
                    $descuento = $detalle['descuento'] ?? 0;
                    $porcentajeImpuesto = $detalle['porcentaje_impuesto'] ?? 0;
                    
                    $subtotal = $cantidad * $precioUnitario;
                    $montoDescuentos += $descuento;
                    $subtotalConDescuento = $subtotal - $descuento;
                    $impuesto = $subtotalConDescuento * ($porcentajeImpuesto / 100);
                    $total = $subtotalConDescuento + $impuesto;
                    
                    $montoSubtotal += $subtotal;
                    $montoImpuestos += $impuesto;
                    
                    DetalleVenta::create([
                        'venta_id' => $venta->id,
                        'producto_id' => $detalle['producto_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'descuento' => $descuento,
                        'porcentaje_impuesto' => $porcentajeImpuesto,
                        'monto_impuesto' => $impuesto,
                        'subtotal' => $subtotal,
                        'total' => $total,
                        'descripcion' => $detalle['descripcion'] ?? null,
                    ]);

                    // Actualizar inventario
                    if ($request->has('almacen_id')) {
                        $inventario = InventarioProducto::where('producto_id', $detalle['producto_id'])
                            ->where('almacen_id', $request->almacen_id)
                            ->first();
                        
                        if ($inventario) {
                            $inventario->decrement('stock_actual', $cantidad);
                        }
                    }
                }
                
                // Actualizar totales de la venta
                $venta->update([
                    'subtotal_bruto_total' => $montoSubtotal,
                    'monto_descuento_total' => $montoDescuentos,
                    'subtotal_neto_total' => $montoSubtotal - $montoDescuentos,
                    'monto_impuesto_total' => $montoImpuestos,
                    'monto_total_venta' => $montoSubtotal - $montoDescuentos + $montoImpuestos,
                ]);
                
                $this->flushCache();
                DB::commit();
                
                $venta->load(['cliente', 'detalles.producto']);
                
                return (new VentaResource($venta))
                    ->additional(['message' => 'Venta creada exitosamente'])
                    ->response()
                    ->setStatusCode(201);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/ventas/{id}',
        summary: 'Obtener una venta específica',
        description: 'Obtiene los detalles completos de una venta por su ID, incluyendo todos sus detalles de línea y relaciones',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la venta',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Venta obtenida exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Venta')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Venta no encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Venta no encontrada')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener venta'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $venta = Venta::with([
                'empresa',
                'sucursal',
                'cliente',
                'usuario',
                'formaPago',
                'detalles.producto'
            ])->findOrFail($id);
            
            $this->authorize('view', $venta);
            
            return (new VentaResource($venta))->response();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateVentaRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/api/ventas/{id}',
        summary: 'Actualizar una venta',
        description: 'Actualiza información de una venta existente (solo observaciones y estado)',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la venta',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true, example: 'Observaciones actualizadas'),
                    new OA\Property(property: 'estado_venta', type: 'string', enum: ['pendiente', 'pagada', 'parcial', 'anulada'], example: 'pagada')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Venta actualizada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Venta'),
                        new OA\Property(property: 'message', type: 'string', example: 'Venta actualizada exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Venta no encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Venta no encontrada')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al actualizar venta'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function update(UpdateVentaRequest $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $venta = Venta::findOrFail($id);
            
            $this->authorize('update', $venta);
            
            // Solo permitir actualizar observaciones y estado
            $venta->update($request->validated());
            $venta->load(['cliente', 'detalles.producto']);
            
            return (new VentaResource($venta))
                ->additional(['message' => 'Venta actualizada exitosamente'])
                ->response();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/ventas/{id}',
        summary: 'Anular una venta',
        description: 'Anula una venta marcándola como anulada, inactiva y eliminada (soft delete)',
        security: [['sanctum' => []]],
        tags: ['Ventas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la venta',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Venta anulada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Venta anulada exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Venta no encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Venta no encontrada')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al anular venta'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $venta = Venta::findOrFail($id);
            
            $this->authorize('delete', $venta);
            
            // Marcar como anulada en lugar de eliminar
            $venta->update([
                'estado_venta' => 'anulada',
                'activo' => false,
                'eliminado' => true
            ]);
            
            $this->flushCache();
            
            return response()->json([
                'message' => 'Venta anulada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Venta no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al anular venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar número de comprobante único
     * 
     * @param int $empresaId
     * @param string $tipoComprobante
     * @return string
     */
    private function generarNumeroComprobante(int $empresaId, string $tipoComprobante): string
    {
        $prefijos = [
            'factura' => 'FAC',
            'tiquete' => 'TIQ',
            'nota_credito' => 'NC',
            'nota_debito' => 'ND',
        ];
        
        $prefijo = $prefijos[$tipoComprobante] ?? 'DOC';
        
        $ultimaVenta = Venta::where('empresa_id', $empresaId)
                            ->where('tipo_comprobante', $tipoComprobante)
                            ->orderBy('id', 'desc')
                            ->first();
        
        $numero = $ultimaVenta ? (int)substr($ultimaVenta->numero_comprobante, -8) + 1 : 1;
        
        return $prefijo . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generar reporte PDF de ventas (async con Queue Job)
     * Sprint 8.4 - Queue Jobs
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/ventas/reportes/pdf',
        summary: 'Generar reporte PDF de ventas (asíncrono)',
        description: 'Encola un job para generar reporte PDF de ventas con filtros. El PDF se genera en background.',
        security: [['sanctum' => []]],
        tags: ['Ventas', 'Reportes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fecha_inicio', 'fecha_fin'],
                properties: [
                    new OA\Property(property: 'fecha_inicio', type: 'string', format: 'date', example: '2025-01-01'),
                    new OA\Property(property: 'fecha_fin', type: 'string', format: 'date', example: '2025-01-31'),
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 5),
                    new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Job encolado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Reporte PDF en proceso. Recibirás notificación cuando esté listo.'),
                        new OA\Property(property: 'job_id', type: 'string', example: 'abc123'),
                    ]
                )
            ),
        ]
    )]
    public function generatePdfReport(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'cliente_id' => 'nullable|exists:clientes,id',
            'sucursal_id' => 'nullable|exists:sucursales,id',
        ]);

        $empresaId = $request->user()->empresa_id;
        
        // Dispatch job asíncrono (Sprint 8.4)
        $job = GeneratePdfReportJob::dispatch(
            reportType: 'ventas',
            empresaId: $empresaId,
            filters: $request->only(['fecha_inicio', 'fecha_fin', 'cliente_id', 'sucursal_id']),
            userId: $request->user()->id
        );

        return response()->json([
            'message' => 'Reporte PDF en proceso. Recibirás notificación cuando esté listo.',
            'job_id' => $job->getJobId(),
        ], 202); // 202 Accepted
    }
}
