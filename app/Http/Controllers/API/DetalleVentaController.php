<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DetalleVenta;
use App\Models\Venta;
use App\Http\Resources\DetalleVentaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DetalleVentaController extends Controller
{
    use HasCacheableQueries;

    protected $cacheTags = ['detalle_ventas', 'ventas', 'transacciones'];
    protected $cacheTTL = 600; // 10 minutos (datos muy dinámicos)

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Alias para getCacheKey (compatibilidad)
     */
    protected function generateCacheKey(string $method, array $params = []): string
    {
        return $this->getCacheKey($method, $params);
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/detalle-ventas',
        summary: 'Listar detalles de ventas',
        description: 'Obtiene un listado paginado de detalles de ventas con filtros',
        security: [['sanctum' => []]],
        tags: ['Detalle Ventas'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'venta_id',
                description: 'Filtrar por venta',
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
                description: 'Listado de detalles obtenido exitosamente',
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
        $this->authorize('viewAny', DetalleVenta::class);

        $cacheKey = $this->generateCacheKey('detalle_ventas.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = DetalleVenta::with(['venta', 'producto', 'tipoImpuesto'])
                ->activos();

            if ($request->filled('venta_id')) {
                $query->where('venta_id', $request->venta_id);
            }

            if ($request->filled('producto_id')) {
                $query->where('producto_id', $request->producto_id);
            }

            $detalles = $query->orderBy('id', 'desc')->paginate($perPage);

            return DetalleVentaResource::collection($detalles);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/detalle-ventas',
        summary: 'Crear detalle de venta',
        description: 'Crea un nuevo detalle de venta',
        security: [['sanctum' => []]],
        tags: ['Detalle Ventas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['venta_id', 'producto_id', 'cantidad', 'precio_unitario'],
                properties: [
                    new OA\Property(property: 'venta_id', type: 'integer', example: 1),
                    new OA\Property(property: 'producto_id', type: 'integer', example: 5),
                    new OA\Property(property: 'numero_linea', type: 'integer', example: 1),
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 10.00),
                    new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', example: 150.00),
                    new OA\Property(property: 'porcentaje_descuento', type: 'number', format: 'decimal', example: 5.00),
                    new OA\Property(property: 'tipo_impuesto_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tasa_impuesto', type: 'number', format: 'decimal', example: 13.00),
                    new OA\Property(property: 'detalle_adicional', type: 'string', example: 'Producto especial'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Detalle creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Detalle de venta creado exitosamente'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(Request $request): DetalleVentaResource|JsonResponse
    {
        $this->authorize('create', DetalleVenta::class);

        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'producto_id' => 'required|exists:productos,id',
            'numero_linea' => 'nullable|integer|min:1',
            'cantidad' => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
            'tipo_impuesto_id' => 'nullable|exists:tipos_impuesto,id',
            'tasa_impuesto' => 'nullable|numeric|min:0',
            'detalle_adicional' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Auto-asignar número de línea si no viene
            if (!isset($validated['numero_linea'])) {
                $maxLinea = DetalleVenta::where('venta_id', $validated['venta_id'])->max('numero_linea') ?? 0;
                $validated['numero_linea'] = $maxLinea + 1;
            }

            $detalle = DetalleVenta::create($validated);

            // Actualizar totales de la venta
            $this->actualizarTotalesVenta($detalle->venta_id);

            DB::commit();
            $this->clearCache();

            return (new DetalleVentaResource($detalle->load(['venta', 'producto', 'tipoImpuesto'])))
                ->additional(['message' => 'Detalle de venta creado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear detalle de venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/detalle-ventas/{id}',
        summary: 'Obtener detalle de venta',
        description: 'Obtiene un detalle de venta específico',
        security: [['sanctum' => []]],
        tags: ['Detalle Ventas'],
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
            ),
            new OA\Response(response: 404, description: 'Detalle no encontrado')
        ]
    )]
    public function show(string $id): DetalleVentaResource
    {
        $detalle = DetalleVenta::with(['venta', 'producto', 'tipoImpuesto'])->findOrFail($id);
        $this->authorize('view', $detalle);

        $cacheKey = $this->generateCacheKey("detalle_ventas.show.{$id}");

        return $this->getCached($cacheKey, function () use ($detalle) {
            return new DetalleVentaResource($detalle);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/detalle-ventas/{id}',
        summary: 'Actualizar detalle de venta',
        description: 'Actualiza un detalle de venta existente',
        security: [['sanctum' => []]],
        tags: ['Detalle Ventas'],
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
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 15.00),
                    new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', example: 175.00),
                    new OA\Property(property: 'porcentaje_descuento', type: 'number', format: 'decimal', example: 10.00),
                    new OA\Property(property: 'detalle_adicional', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function update(Request $request, string $id): DetalleVentaResource|JsonResponse
    {
        $detalle = DetalleVenta::findOrFail($id);
        $this->authorize('update', $detalle);

        $validated = $request->validate([
            'cantidad' => 'sometimes|numeric|min:0.01',
            'precio_unitario' => 'sometimes|numeric|min:0',
            'porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
            'tasa_impuesto' => 'nullable|numeric|min:0',
            'detalle_adicional' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $detalle->update($validated);

            // Actualizar totales de la venta
            $this->actualizarTotalesVenta($detalle->venta_id);

            DB::commit();
            $this->clearCache();

            return (new DetalleVentaResource($detalle->fresh(['venta', 'producto', 'tipoImpuesto'])))
                ->additional(['message' => 'Detalle de venta actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar detalle de venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/detalle-ventas/{id}',
        summary: 'Eliminar detalle de venta',
        description: 'Elimina (soft delete) un detalle de venta',
        security: [['sanctum' => []]],
        tags: ['Detalle Ventas'],
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
                description: 'Detalle eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'message', type: 'string')]
                )
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $detalle = DetalleVenta::findOrFail($id);
        $this->authorize('delete', $detalle);

        DB::beginTransaction();
        try {
            $ventaId = $detalle->venta_id;

            $detalle->update([
                'eliminado' => true,
                'activo' => false
            ]);

            // Actualizar totales de la venta
            $this->actualizarTotalesVenta($ventaId);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Detalle de venta eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar detalle de venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar totales de la venta recalculando desde los detalles activos
     */
    private function actualizarTotalesVenta($ventaId)
    {
        $venta = Venta::findOrFail($ventaId);

        $detalles = DetalleVenta::where('venta_id', $ventaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->get();

        $subtotalBruto = $detalles->sum('subtotal_linea');
        $montoDescuento = $detalles->sum('monto_descuento');
        $subtotalNeto = $detalles->sum('subtotal_con_descuento');
        $montoImpuesto = $detalles->sum('monto_impuesto');
        $montoTotal = $detalles->sum('total_linea');

        $venta->update([
            'subtotal_bruto_total' => $subtotalBruto,
            'monto_descuento_total' => $montoDescuento,
            'subtotal_neto_total' => $subtotalNeto,
            'monto_impuesto_total' => $montoImpuesto,
            'monto_total_venta' => $montoTotal,
        ]);
    }
}
