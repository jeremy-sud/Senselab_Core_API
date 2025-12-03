<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Http\Requests\UpdateEntradaInventarioRequest;
use App\Http\Resources\EntradaInventarioResource;
use App\Models\EntradaInventario;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador para Entradas de Inventario
 *
 * Gestiona el registro de entradas de mercancía al inventario (compras,
 * ajustes positivos, devoluciones de clientes, etc.).
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class EntradaInventarioController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /**
     * Tags para invalidación de cache
     * @var array<string>
     */
    protected array $cacheTags = ['entradas-inventario', 'inventario'];

    /**
     * TTL del cache en segundos (20 minutos)
     * Datos dinámicos: entradas cambian frecuentemente durante operaciones
     * @var int
     */
    protected int $cacheTTL = 1200;
    /**
     * Listar todas las entradas de inventario de la empresa
     */
    #[OA\Get(
        path: '/api/entradas-inventario',
        summary: 'Listar entradas de inventario',
        description: 'Obtiene el listado paginado de todas las entradas de inventario de la empresa con sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de entradas de inventario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/EntradaInventario')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 25),
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EntradaInventario::class);

        $cacheKey = $this->generateCacheKey('entradas_inventario.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = EntradaInventario::with(['proveedor', 'bodega', 'usuario'])
                ->activos();

            if ($request->filled('proveedor_id')) {
                $query->where('proveedor_id', $request->proveedor_id);
            }

            if ($request->filled('bodega_id')) {
                $query->where('bodega_id', $request->bodega_id);
            }

            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $query->whereBetween('fecha_entrada', [$request->fecha_inicio, $request->fecha_fin]);
            }

            $entradas = $query->orderBy('fecha_entrada', 'desc')->paginate($perPage);

            return EntradaInventarioResource::collection($entradas);
        });
    }

    /**
     * Crear nueva entrada de inventario
     */
    #[OA\Post(
        path: '/api/entradas-inventario',
        summary: 'Crear entrada de inventario',
        description: 'Registra una nueva entrada de inventario en estado Pendiente. Se debe agregar productos mediante los detalles',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fecha_entrada'],
                properties: [
                    new OA\Property(property: 'almacen_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_entrada', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
                    new OA\Property(property: 'tipo_entrada', type: 'string', enum: ['Compra', 'Ajuste Positivo', 'Transferencia', 'Devolución Cliente', 'Producción'], example: 'Compra'),
                    new OA\Property(property: 'orden_compra_id', type: 'integer', example: 5),
                    new OA\Property(property: 'proveedor_id', type: 'integer', example: 3),
                    new OA\Property(property: 'documento_referencia', type: 'string', example: 'FACT-2025-001'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Recepción completa sin novedades')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entrada creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Entrada de inventario creada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/EntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos'),
            new OA\Response(response: 500, description: 'Error al crear la entrada')
        ]
    )]
    public function store(Request $request): EntradaInventarioResource|JsonResponse
    {
        $this->authorize('create', EntradaInventario::class);

        $validated = $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'bodega_id' => 'required|exists:bodegas,id',
            'fecha_entrada' => 'required|date',
            'numero_factura_proveedor' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:productos,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'required|numeric|min:0',
            'detalles.*.lote' => 'nullable|string|max:50',
            'detalles.*.fecha_vencimiento' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $validated['usuario_id'] = auth('sanctum')->id();
            $validated['estado'] = 'pendiente'; // Estado inicial

            $entrada = EntradaInventario::create($validated);

            foreach ($validated['detalles'] as $detalle) {
                $entrada->detalles()->create($detalle);
            }

            // Actualizar total
            $this->actualizarTotalEntrada($entrada->id);

            DB::commit();
            $this->clearCache();

            return (new EntradaInventarioResource($entrada->load(['proveedor', 'bodega', 'usuario', 'detalles.producto'])))
                ->additional(['message' => 'Entrada de inventario creada exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear entrada de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una entrada específica
     */
    #[OA\Get(
        path: '/api/entradas-inventario/{id}',
        summary: 'Obtener entrada de inventario',
        description: 'Retorna los datos de una entrada específica con todas sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/EntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada')
        ]
    )]
    public function show(string $id): EntradaInventarioResource
    {
        $entrada = EntradaInventario::with(['proveedor', 'bodega', 'usuario', 'detalles.producto'])->findOrFail($id);
        $this->authorize('view', $entrada);

        $cacheKey = $this->generateCacheKey("entradas_inventario.show.{$id}");

        return $this->getCached($cacheKey, function () use ($entrada) {
            return new EntradaInventarioResource($entrada);
        });
    }

    /**
     * Actualizar entrada de inventario
     */
    #[OA\Put(
        path: '/api/entradas-inventario/{id}',
        summary: 'Actualizar entrada de inventario',
        description: 'Modifica los datos de una entrada existente. Solo se permite actualizar si el estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'almacen_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_entrada', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
                    new OA\Property(property: 'tipo_entrada', type: 'string', example: 'Compra'),
                    new OA\Property(property: 'orden_compra_id', type: 'integer', example: 5),
                    new OA\Property(property: 'proveedor_id', type: 'integer', example: 3),
                    new OA\Property(property: 'documento_referencia', type: 'string', example: 'FACT-2025-001'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Actualizado')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Entrada de inventario actualizada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/EntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada'),
            new OA\Response(response: 422, description: 'No se puede modificar una entrada ya procesada'),
            new OA\Response(response: 500, description: 'Error al actualizar')
        ]
    )]
    public function update(Request $request, string $id): EntradaInventarioResource|JsonResponse
    {
        $entrada = EntradaInventario::findOrFail($id);
        $this->authorize('update', $entrada);

        if ($entrada->estado !== 'pendiente') {
            return response()->json(['message' => 'No se puede modificar una entrada procesada o anulada'], 422);
        }

        $validated = $request->validate([
            'proveedor_id' => 'sometimes|exists:proveedores,id',
            'bodega_id' => 'sometimes|exists:bodegas,id',
            'fecha_entrada' => 'sometimes|date',
            'numero_factura_proveedor' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $entrada->update($validated);

            DB::commit();
            $this->clearCache();

            return (new EntradaInventarioResource($entrada->fresh(['proveedor', 'bodega', 'usuario'])))
                ->additional(['message' => 'Entrada de inventario actualizada exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar entrada de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar entrada de inventario
     */
    #[OA\Delete(
        path: '/api/entradas-inventario/{id}',
        summary: 'Eliminar entrada de inventario',
        description: 'Elimina una entrada de inventario. Solo se permite eliminar si el estado es Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada eliminada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Entrada de inventario eliminada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada'),
            new OA\Response(response: 422, description: 'No se puede eliminar una entrada ya procesada')
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('delete', $entrada);

        if ($entrada->estado !== 'pendiente') {
            return response()->json(['message' => 'No se puede eliminar una entrada procesada o anulada'], 422);
        }

        try {
            $entrada->delete();
            $this->clearCache();

            return response()->json([
                'message' => 'Entrada de inventario eliminada exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar entrada de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar entrada de inventario
     */
    #[OA\Put(
        path: '/api/entradas-inventario/{id}/procesar',
        summary: 'Procesar entrada de inventario',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada procesada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Entrada procesada exitosamente, stock actualizado'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/EntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada'),
            new OA\Response(
                response: 422,
                description: 'Validación fallida',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'La entrada ya fue procesada anteriormente')
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error al procesar')
        ]
    )]
    public function procesar(Request $request, int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->with('detalles.producto')
            ->findOrFail($id);

        // Validaciones con abort para reducir returns
        abort_if(
            $entrada->estado === 'Procesada',
            422,
            'La entrada ya fue procesada anteriormente'
        );

        abort_if(
            $entrada->detalles->isEmpty(),
            422,
            'No se puede procesar una entrada sin productos'
        );

        DB::beginTransaction();
        try {
            // Actualizar stock de cada producto
            foreach ($entrada->detalles as $detalle) {
                $inventario = DB::table('inventarios')
                    ->where('producto_id', $detalle->producto_id)
                    ->where('almacen_id', $entrada->almacen_id)
                    ->first();

                if ($inventario) {
                    DB::table('inventarios')
                        ->where('id', $inventario->id)
                        ->increment('cantidad_actual', (float) $detalle->cantidad);
                } else {
                    DB::table('inventarios')->insert([
                        'producto_id' => $detalle->producto_id,
                        'almacen_id' => $entrada->almacen_id,
                        'cantidad_actual' => (float) $detalle->cantidad,
                        'cantidad_minima' => 0,
                        'creado_en' => now(),
                        'actualizado_en' => now()
                    ]);
                }
            }

            $entrada->update(['estado' => 'Procesada']);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada procesada exitosamente, stock actualizado',
                'data' => new EntradaInventarioResource($entrada->fresh(['almacen', 'proveedor', 'detalles']))
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            abort(500, 'Error al procesar la entrada: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar entrada de inventario
     */
    #[OA\Post(
        path: '/api/entradas-inventario/{id}/cancelar',
        summary: 'Cancelar entrada de inventario',
        description: 'Cambia el estado de la entrada a Cancelada. Solo se permite cancelar entradas en estado Pendiente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entrada cancelada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Entrada cancelada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/EntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada'),
            new OA\Response(response: 422, description: 'No se puede cancelar una entrada ya procesada')
        ]
    )]
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una entrada ya procesada'
            ], 422);
        }

        $entrada->update(['estado' => 'Cancelada']);

        return response()->json([
            'success' => true,
            'message' => 'Entrada cancelada exitosamente',
            'data' => new EntradaInventarioResource($entrada)
        ]);
    }

    /**
     * Obtener entradas por proveedor
     */
    #[OA\Get(
        path: '/api/entradas-inventario/proveedor/{proveedorId}',
        summary: 'Obtener entradas por proveedor',
        description: 'Lista todas las entradas de inventario asociadas a un proveedor específico',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'proveedorId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entradas del proveedor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EntradaInventario')),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 10)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function porProveedor(Request $request, int $proveedorId): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $entradas = EntradaInventario::where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->with(['almacen', 'proveedor', 'detalles'])
            ->orderBy('fecha_entrada', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => EntradaInventarioResource::collection($entradas),
            'meta' => [
                'current_page' => $entradas->currentPage(),
                'total' => $entradas->total()
            ]
        ]);
    }

    /**
     * Obtener entradas por almacén
     */
    #[OA\Get(
        path: '/api/entradas-inventario/almacen/{almacenId}',
        summary: 'Obtener entradas por almacén',
        description: 'Lista todas las entradas de inventario de un almacén específico',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'almacenId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entradas del almacén',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EntradaInventario')),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'total', type: 'integer', example: 15)
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
        $empresaId = $this->getEmpresaId();

        $entradas = EntradaInventario::where('empresa_id', $empresaId)
            ->where('almacen_id', $almacenId)
            ->with(['almacen', 'proveedor', 'detalles'])
            ->orderBy('fecha_entrada', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => EntradaInventarioResource::collection($entradas),
            'meta' => [
                'current_page' => $entradas->currentPage(),
                'total' => $entradas->total()
            ]
        ]);
    }

    /**
     * Resumen de entradas por tipo
     */
    #[OA\Get(
        path: '/api/entradas-inventario/resumen-por-tipo',
        summary: 'Resumen de entradas por tipo',
        description: 'Genera estadísticas agrupadas por tipo de entrada mostrando cantidad total y monto total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
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
                                    new OA\Property(property: 'tipo_entrada', type: 'string', example: 'Compra'),
                                    new OA\Property(property: 'total_entradas', type: 'integer', example: 15),
                                    new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', example: 125000.50)
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
        $empresaId = $this->getEmpresaId();

        $resumen = EntradaInventario::where('empresa_id', $empresaId)
            ->selectRaw('tipo_entrada, COUNT(*) as total_entradas, SUM(monto_total) as monto_total')
            ->groupBy('tipo_entrada')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Obtener entradas pendientes
     */
    #[OA\Get(
        path: '/api/entradas-inventario/pendientes',
        summary: 'Obtener entradas pendientes',
        description: 'Lista todas las entradas de inventario en estado Pendiente ordenadas por fecha de entrada ascendente',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Entradas pendientes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EntradaInventario'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function pendientes(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $entradas = EntradaInventario::where('empresa_id', $empresaId)
            ->where('estado', 'Pendiente')
            ->with(['almacen', 'proveedor', 'detalles'])
            ->orderBy('fecha_entrada', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => EntradaInventarioResource::collection($entradas)
        ]);
    }
}
