<?php

/**
 * API Controller para `DetalleOrdenCompra`.
 *
 * Administra las líneas de detalle asociadas a órdenes de compra. Incluye
 * endpoints paginados y operaciones de creación con validaciones.
 */
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrdenCompra;
use App\Models\OrdenCompra;
use App\Http\Resources\DetalleOrdenCompraResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DetalleOrdenCompraController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['detalle_ordenes_compra', 'ordenes_compra', 'compras'];
    protected int $cacheTTL = 1200; // 20 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Alias para getCacheKey (compatibilidad)
     */
    /**
     * @param array<string, mixed> $params
     */
    /**
     * @param array<string, mixed> $params
     */
    protected function generateCacheKey(string $method, array $params = []): string
    {
        return $this->getCacheKey($method, $params);
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/detalle-ordenes-compra',
        summary: 'Listar detalles de órdenes de compra',
        description: 'Obtiene un listado paginado de detalles de órdenes de compra',
        security: [['sanctum' => []]],
        tags: ['Detalle Órdenes Compra'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'orden_compra_id',
                description: 'Filtrar por orden de compra',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'producto_id',
                description: 'Filtrar por producto',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', DetalleOrdenCompra::class);

        $cacheKey = $this->generateCacheKey('detalle_ordenes_compra.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = DetalleOrdenCompra::with(['ordenCompra', 'producto'])
                ->activos();

            if ($request->filled('orden_compra_id')) {
                $query->where('orden_compra_id', $request->orden_compra_id);
            }

            if ($request->filled('producto_id')) {
                $query->where('producto_id', $request->producto_id);
            }

            $detalles = $query->orderBy('id', 'desc')->paginate($perPage);

            return DetalleOrdenCompraResource::collection($detalles);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/detalle-ordenes-compra',
        summary: 'Crear detalle de orden de compra',
        description: 'Crea un nuevo detalle de orden de compra',
        security: [['sanctum' => []]],
        tags: ['Detalle Órdenes Compra'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['orden_compra_id', 'producto_id', 'cantidad', 'precio_unitario'],
                properties: [
                    new OA\Property(property: 'orden_compra_id', type: 'integer', example: 1),
                    new OA\Property(property: 'producto_id', type: 'integer', example: 5),
                    new OA\Property(property: 'numero_linea', type: 'integer', example: 1),
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 50.00),
                    new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', example: 125.00),
                    new OA\Property(property: 'porcentaje_impuesto', type: 'number', format: 'decimal', example: 13.00),
                    new OA\Property(property: 'detalle_adicional', type: 'string', example: 'Producto importado'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Detalle creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): DetalleOrdenCompraResource|JsonResponse
    {
        $this->authorize('create', DetalleOrdenCompra::class);

        $validated = $request->validate([
            'orden_compra_id' => 'required|exists:ordenes_compra,id',
            'producto_id' => 'required|exists:productos,id',
            'numero_linea' => 'nullable|integer|min:1',
            'cantidad' => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'porcentaje_impuesto' => 'nullable|numeric|min:0',
            'detalle_adicional' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Auto-asignar número de línea si no viene
            if (!isset($validated['numero_linea'])) {
                $maxLinea = DetalleOrdenCompra::where('orden_compra_id', $validated['orden_compra_id'])
                    ->max('numero_linea') ?? 0;
                $validated['numero_linea'] = $maxLinea + 1;
            }

            $detalle = DetalleOrdenCompra::create($validated);

            // Actualizar totales de la orden
            $this->actualizarTotalesOrden($detalle->orden_compra_id);

            DB::commit();
            $this->clearCache();

            return (new DetalleOrdenCompraResource($detalle->load(['ordenCompra', 'producto'])))
                ->additional(['message' => 'Detalle de orden de compra creado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/detalle-ordenes-compra/{id}',
        summary: 'Obtener detalle de orden de compra',
        description: 'Obtiene un detalle específico',
        security: [['sanctum' => []]],
        tags: ['Detalle Órdenes Compra'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del detalle',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle obtenido exitosamente',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')])
            )
        ]
    )]
    public function show(string $id): DetalleOrdenCompraResource
    {
        $detalle = DetalleOrdenCompra::with(['ordenCompra', 'producto'])->findOrFail($id);
        $this->authorize('view', $detalle);

        $cacheKey = $this->generateCacheKey("detalle_ordenes_compra.show.{$id}");

        return $this->getCached($cacheKey, function () use ($detalle) {
            return new DetalleOrdenCompraResource($detalle);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/detalle-ordenes-compra/{id}',
        summary: 'Actualizar detalle de orden de compra',
        description: 'Actualiza un detalle existente',
        security: [['sanctum' => []]],
        tags: ['Detalle Órdenes Compra'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del detalle',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 75.00),
                    new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', example: 130.00),
                    new OA\Property(property: 'detalle_adicional', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): DetalleOrdenCompraResource|JsonResponse
    {
        $detalle = DetalleOrdenCompra::findOrFail($id);
        $this->authorize('update', $detalle);

        $validated = $request->validate([
            'cantidad' => 'sometimes|numeric|min:0.01',
            'precio_unitario' => 'sometimes|numeric|min:0',
            'porcentaje_impuesto' => 'nullable|numeric|min:0',
            'detalle_adicional' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $detalle->update($validated);

            // Actualizar totales de la orden
            $this->actualizarTotalesOrden($detalle->orden_compra_id);

            DB::commit();
            $this->clearCache();

            return (new DetalleOrdenCompraResource($detalle->fresh(['ordenCompra', 'producto'])))
                ->additional(['message' => 'Detalle actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/detalle-ordenes-compra/{id}',
        summary: 'Eliminar detalle de orden de compra',
        description: 'Elimina (soft delete) un detalle',
        security: [['sanctum' => []]],
        tags: ['Detalle Órdenes Compra'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del detalle',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle eliminado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $detalle = DetalleOrdenCompra::findOrFail($id);
        $this->authorize('delete', $detalle);

        DB::beginTransaction();
        try {
            $ordenId = $detalle->orden_compra_id;

            $detalle->update([
                'eliminado' => true,
                'activo' => false
            ]);

            // Actualizar totales de la orden
            $this->actualizarTotalesOrden($ordenId);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Detalle eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar totales de la orden recalculando desde detalles activos
     *
     * @param int|string $ordenId
     */
    private function actualizarTotalesOrden(int|string $ordenId): void
    {
        $orden = OrdenCompra::findOrFail($ordenId);

        $detalles = DetalleOrdenCompra::where('orden_compra_id', $ordenId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->get();

        $subtotal = $detalles->sum('subtotal_linea');
        $impuestoTotal = $detalles->sum('monto_impuesto');
        $total = $detalles->sum('total_linea');

        $orden->update([
            'subtotal' => $subtotal,
            'impuesto_total' => $impuestoTotal,
            'total_orden' => $total,
        ]);
    }
}
