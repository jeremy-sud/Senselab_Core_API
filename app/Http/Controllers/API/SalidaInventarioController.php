<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalidaInventarioRequest;
use App\Http\Requests\UpdateSalidaInventarioRequest;
use App\Http\Resources\SalidaInventarioResource;
use App\Models\SalidaInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador para Salidas de Inventario
 * 
 * Gestiona el registro de salidas de mercancía del inventario (ventas,
 * consumo interno, mermas, ajustes negativos, etc.).
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class SalidaInventarioController extends Controller
{
    /**
     * Listar todas las salidas de inventario de la empresa
     */
    #[OA\Get(
        path: '/api/salidas-inventario',
        summary: 'Listar salidas de inventario',
        description: 'Obtiene el listado paginado de todas las salidas de inventario de la empresa con sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de salidas de inventario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SalidaInventario')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 30),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalidaInventario::class);
        
        $empresaId = $request->user()->empresa_id;

        $salidas = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'cliente', 'proveedor', 'venta', 'detalles.producto'])
            ->orderBy('fecha_salida', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SalidaInventarioResource::collection($salidas),
            'meta' => [
                'current_page' => $salidas->currentPage(),
                'total' => $salidas->total(),
                'per_page' => $salidas->perPage()
            ]
        ]);
    }

    /**
     * Crear nueva salida de inventario
     */
    #[OA\Post(
        path: '/api/salidas-inventario',
        summary: 'Crear salida de inventario',
        description: 'Registra una nueva salida de inventario en estado Pendiente. Se debe agregar productos mediante los detalles',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fecha_salida'],
                properties: [
                    new OA\Property(property: 'almacen_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_salida', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
                    new OA\Property(property: 'tipo_salida', type: 'string', enum: ['Venta', 'Ajuste Negativo', 'Devolución Proveedor', 'Transferencia', 'Consumo Interno'], example: 'Venta'),
                    new OA\Property(property: 'venta_id', type: 'integer', example: 12),
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 8),
                    new OA\Property(property: 'proveedor_id', type: 'integer', example: 3),
                    new OA\Property(property: 'documento_referencia', type: 'string', example: 'FACT-V-2025-050'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Entregado al cliente sin novedades'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Venta directa mostrador')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Salida creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Salida de inventario creada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos'),
            new OA\Response(response: 500, description: 'Error al crear la salida')
        ]
    )]
    public function store(StoreSalidaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', SalidaInventario::class);
        
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();
        try {
            $salida = SalidaInventario::create([
                'empresa_id' => $empresaId,
                'almacen_id' => $request->almacen_id,
                'fecha_salida' => $request->fecha_salida,
                'tipo_salida' => $request->tipo_salida,
                'venta_id' => $request->venta_id,
                'cliente_id' => $request->cliente_id,
                'proveedor_id' => $request->proveedor_id,
                'documento_referencia' => $request->documento_referencia,
                'observaciones' => $request->observaciones,
                'descripcion' => $request->descripcion,
                'estado' => 'Pendiente',
                'monto_total' => 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida de inventario creada exitosamente',
                'data' => new SalidaInventarioResource($salida->load(['almacen', 'cliente']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la salida de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una salida específica
     */
    #[OA\Get(
        path: '/api/salidas-inventario/{id}',
        summary: 'Obtener salida de inventario',
        description: 'Retorna los datos de una salida específica con todas sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salida encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada')
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'cliente', 'proveedor', 'venta', 'detalles.producto.unidadMedida'])
            ->findOrFail($id);
        
        $this->authorize('view', $salida);

        return response()->json([
            'success' => true,
            'data' => new SalidaInventarioResource($salida)
        ]);
    }

    /**
     * Actualizar salida de inventario
     */
    #[OA\Put(
        path: '/api/salidas-inventario/{id}',
        summary: 'Actualizar salida de inventario',
        description: 'Modifica los datos de una salida existente. Solo se permite actualizar si el estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'almacen_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_salida', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
                    new OA\Property(property: 'tipo_salida', type: 'string', example: 'Venta'),
                    new OA\Property(property: 'venta_id', type: 'integer', example: 12),
                    new OA\Property(property: 'cliente_id', type: 'integer', example: 8),
                    new OA\Property(property: 'proveedor_id', type: 'integer', example: 3),
                    new OA\Property(property: 'documento_referencia', type: 'string', example: 'FACT-V-2025-050'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Actualizado'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Descripción actualizada')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salida actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Salida de inventario actualizada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada'),
            new OA\Response(response: 422, description: 'No se puede modificar una salida ya procesada'),
            new OA\Response(response: 500, description: 'Error al actualizar')
        ]
    )]
    public function update(UpdateSalidaInventarioRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);
        
        $this->authorize('update', $salida);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una salida ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salida->update($request->only([
                'almacen_id',
                'fecha_salida',
                'tipo_salida',
                'venta_id',
                'cliente_id',
                'proveedor_id',
                'documento_referencia',
                'observaciones',
                'descripcion'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida de inventario actualizada exitosamente',
                'data' => new SalidaInventarioResource($salida->load(['almacen', 'cliente']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la salida',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar salida de inventario
     */
    #[OA\Delete(
        path: '/api/salidas-inventario/{id}',
        summary: 'Eliminar salida de inventario',
        description: 'Elimina una salida de inventario. Solo se permite eliminar si el estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salida eliminada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Salida de inventario eliminada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada'),
            new OA\Response(response: 422, description: 'No se puede eliminar una salida ya procesada')
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);
        
        $this->authorize('delete', $salida);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una salida ya procesada'
            ], 422);
        }

        $salida->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salida de inventario eliminada exitosamente'
        ]);
    }

    /**
     * Procesar salida de inventario (actualiza stock)
     */
    #[OA\Post(
        path: '/api/salidas-inventario/{id}/procesar',
        summary: 'Procesar salida de inventario',
        description: 'Cambia el estado de la salida a Procesada y reduce las cantidades en el inventario. Valida que exista stock suficiente. Esta acción es irreversible',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salida procesada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Salida procesada exitosamente, stock actualizado'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada'),
            new OA\Response(
                response: 422,
                description: 'Validación fallida',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Stock insuficiente para el producto ID: 15')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error al procesar')
        ]
    )]
    public function procesar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->with('detalles.producto')
            ->findOrFail($id);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'La salida ya fue procesada anteriormente'
            ], 422);
        }

        if ($salida->detalles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede procesar una salida sin productos'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Verificar y reducir stock de cada producto
            foreach ($salida->detalles as $detalle) {
                $inventario = DB::table('inventarios')
                    ->where('producto_id', $detalle->producto_id)
                    ->where('almacen_id', $salida->almacen_id)
                    ->first();

                if (!$inventario || $inventario->cantidad_actual < $detalle->cantidad) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para el producto ID: {$detalle->producto_id}"
                    ], 422);
                }

                DB::table('inventarios')
                    ->where('id', $inventario->id)
                    ->decrement('cantidad_actual', $detalle->cantidad);
            }

            $salida->update(['estado' => 'Procesada']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salida procesada exitosamente, stock actualizado',
                'data' => new SalidaInventarioResource($salida->fresh(['almacen', 'cliente', 'detalles']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la salida',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar salida de inventario
     */
    #[OA\Post(
        path: '/api/salidas-inventario/{id}/cancelar',
        summary: 'Cancelar salida de inventario',
        description: 'Cambia el estado de la salida a Cancelada. Solo se permite cancelar salidas en estado Pendiente. Para salidas procesadas debe crear una entrada de ajuste',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salida cancelada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Salida cancelada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada'),
            new OA\Response(
                response: 422,
                description: 'No se puede cancelar una salida ya procesada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'No se puede cancelar una salida ya procesada. Debe crear una entrada de ajuste.')
                    ]
                )
            )
        ]
    )]
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una salida ya procesada. Debe crear una entrada de ajuste.'
            ], 422);
        }

        $salida->update(['estado' => 'Cancelada']);

        return response()->json([
            'success' => true,
            'message' => 'Salida cancelada exitosamente',
            'data' => new SalidaInventarioResource($salida)
        ]);
    }

    /**
     * Obtener salidas por cliente
     */
    #[OA\Get(
        path: '/api/salidas-inventario/cliente/{clienteId}',
        summary: 'Obtener salidas por cliente',
        description: 'Lista todas las salidas de inventario asociadas a un cliente específico',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'clienteId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salidas del cliente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SalidaInventario')),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 12)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function porCliente(Request $request, int $clienteId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salidas = SalidaInventario::where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderBy('fecha_salida', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SalidaInventarioResource::collection($salidas),
            'meta' => [
                'current_page' => $salidas->currentPage(),
                'total' => $salidas->total()
            ]
        ]);
    }

    /**
     * Obtener salidas por almacén
     */
    #[OA\Get(
        path: '/api/salidas-inventario/almacen/{almacenId}',
        summary: 'Obtener salidas por almacén',
        description: 'Lista todas las salidas de inventario de un almacén específico',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'almacenId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salidas del almacén',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SalidaInventario')),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 18)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function porAlmacen(Request $request, int $almacenId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salidas = SalidaInventario::where('empresa_id', $empresaId)
            ->where('almacen_id', $almacenId)
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderBy('fecha_salida', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => SalidaInventarioResource::collection($salidas),
            'meta' => [
                'current_page' => $salidas->currentPage(),
                'total' => $salidas->total()
            ]
        ]);
    }

    /**
     * Resumen de salidas por tipo
     */
    #[OA\Get(
        path: '/api/salidas-inventario/resumen-por-tipo',
        summary: 'Resumen de salidas por tipo',
        description: 'Genera estadísticas agrupadas por tipo de salida mostrando cantidad total y monto total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen estadístico',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'tipo_salida', type: 'string', example: 'Venta'),
                                    new OA\Property(property: 'total_salidas', type: 'integer', example: 25),
                                    new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', example: 85000.75)
                                ],
                                type: 'object'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function resumenPorTipo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = SalidaInventario::where('empresa_id', $empresaId)
            ->selectRaw('tipo_salida, COUNT(*) as total_salidas, SUM(monto_total) as monto_total')
            ->groupBy('tipo_salida')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Obtener salidas pendientes
     */
    #[OA\Get(
        path: '/api/salidas-inventario/pendientes',
        summary: 'Obtener salidas pendientes',
        description: 'Lista todas las salidas de inventario en estado Pendiente ordenadas por fecha de salida ascendente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Salidas pendientes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SalidaInventario'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function pendientes(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salidas = SalidaInventario::where('empresa_id', $empresaId)
            ->where('estado', 'Pendiente')
            ->with(['almacen', 'cliente', 'detalles'])
            ->orderBy('fecha_salida', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => SalidaInventarioResource::collection($salidas)
        ]);
    }
}
