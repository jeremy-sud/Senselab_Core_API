<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDetalleEntradaInventarioRequest;
use App\Http\Requests\UpdateDetalleEntradaInventarioRequest;
use App\Http\Resources\DetalleEntradaInventarioResource;
use App\Models\DetalleEntradaInventario;
use App\Models\EntradaInventario;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador para Detalle de Entradas de Inventario
 * 
 * Gestiona los productos específicos incluidos en cada entrada de inventario.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleEntradaInventarioController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['detalles-entradas-inventario', 'inventario', 'entradas'];
    protected int $cacheTTL = 1800; // 30min - inventory detail semi-dynamic
    /**
     * Listar detalles de una entrada específica
     */
    #[OA\Get(
        path: '/api/entradas-inventario/{entradaId}/detalles',
        summary: 'Listar detalles de una entrada',
        description: 'Obtiene todos los productos incluidos en una entrada de inventario específica',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'entradaId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles de la entrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DetalleEntradaInventario'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Entrada no encontrada')
        ]
    )]
    public function index(Request $request, int $entradaId): JsonResponse
    {
        $this->authorize('viewAny', DetalleEntradaInventario::class);
        
        $empresaId = $request->user()->empresa_id;

        $cacheKey = $this->getCacheKey('index', ['entrada_id' => $entradaId]);

        return $this->cacheQueryIfEnabled($cacheKey, function() use ($empresaId, $entradaId) {
            $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($entradaId);

            $detalles = DetalleEntradaInventario::where('entrada_inventario_id', $entradaId)
                ->with(['producto.unidadMedida', 'producto.categoriaProducto'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => DetalleEntradaInventarioResource::collection($detalles)
            ]);
        });
    }

    /**
     * Agregar producto a entrada de inventario
     */
    #[OA\Post(
        path: '/api/detalles-entradas-inventario',
        summary: 'Agregar producto a entrada',
        description: 'Agrega un producto a una entrada de inventario. Solo se permite si el estado de la entrada es Pendiente. Actualiza automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['entrada_inventario_id', 'producto_id', 'cantidad', 'costo_unitario'],
                properties: [
                    new OA\Property(property: 'entrada_inventario_id', type: 'integer', example: 1),
                    new OA\Property(property: 'producto_id', type: 'integer', example: 15),
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 50.00),
                    new OA\Property(property: 'costo_unitario', type: 'number', format: 'decimal', example: 25.50),
                    new OA\Property(property: 'lote', type: 'string', example: 'LOTE-2025-A123'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2026-12-31')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto agregado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Producto agregado a la entrada exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleEntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'No se pueden agregar productos a una entrada ya procesada'),
            new OA\Response(response: 500, description: 'Error al agregar el producto')
        ]
    )]
    public function store(StoreDetalleEntradaInventarioRequest $request): JsonResponse
    {
        $this->authorize('create', DetalleEntradaInventario::class);
        
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->findOrFail($request->entrada_inventario_id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden agregar productos a una entrada ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = $request->cantidad * $request->costo_unitario;

            $detalle = DetalleEntradaInventario::create([
                'entrada_inventario_id' => $request->entrada_inventario_id,
                'producto_id' => $request->producto_id,
                'cantidad' => $request->cantidad,
                'costo_unitario' => $request->costo_unitario,
                'subtotal' => $subtotal,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento
            ]);

            // Actualizar monto total de la entrada
            $entrada->increment('monto_total', $subtotal);

            DB::commit();
            
            $this->flushCache();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado a la entrada exitosamente',
                'data' => new DetalleEntradaInventarioResource($detalle->load('producto'))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalle específico
     */
    #[OA\Get(
        path: '/api/detalles-entradas-inventario/{id}',
        summary: 'Obtener detalle de entrada',
        description: 'Retorna la información de un detalle específico de entrada de inventario',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleEntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Detalle no encontrado')
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $detalle = DetalleEntradaInventario::with(['producto', 'entradaInventario'])
            ->findOrFail($id);

        $empresaId = $request->user()->empresa_id;
        if ($detalle->entradaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }
        
        $this->authorize('view', $detalle);

        return response()->json([
            'success' => true,
            'data' => new DetalleEntradaInventarioResource($detalle)
        ]);
    }

    /**
     * Actualizar detalle de entrada
     */
    #[OA\Put(
        path: '/api/detalles-entradas-inventario/{id}',
        summary: 'Actualizar detalle de entrada',
        description: 'Modifica un detalle de entrada. Solo se permite si el estado de la entrada es Pendiente. Ajusta automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cantidad', 'costo_unitario'],
                properties: [
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 60.00),
                    new OA\Property(property: 'costo_unitario', type: 'number', format: 'decimal', example: 26.00),
                    new OA\Property(property: 'lote', type: 'string', example: 'LOTE-2025-B456'),
                    new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2026-12-31')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle actualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Detalle actualizado exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleEntradaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Detalle no encontrado'),
            new OA\Response(response: 422, description: 'No se puede modificar una entrada ya procesada'),
            new OA\Response(response: 500, description: 'Error al actualizar')
        ]
    )]
    public function update(UpdateDetalleEntradaInventarioRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetalleEntradaInventario::with('entradaInventario')
            ->findOrFail($id);

        if ($detalle->entradaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }
        
        $this->authorize('update', $detalle);

        if ($detalle->entradaInventario->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una entrada ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotalAnterior = $detalle->subtotal;
            $nuevoSubtotal = $request->cantidad * $request->costo_unitario;

            $detalle->update([
                'cantidad' => $request->cantidad,
                'costo_unitario' => $request->costo_unitario,
                'subtotal' => $nuevoSubtotal,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento
            ]);

            // Ajustar monto total de la entrada
            $diferencia = $nuevoSubtotal - $subtotalAnterior;
            $detalle->entradaInventario->increment('monto_total', $diferencia);

            $this->flushCache(['detalles-entradas-inventario', 'inventario', 'entradas']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detalle actualizado exitosamente',
                'data' => new DetalleEntradaInventarioResource($detalle->fresh('producto'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el detalle',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar producto de la entrada
     */
    #[OA\Delete(
        path: '/api/detalles-entradas-inventario/{id}',
        summary: 'Eliminar detalle de entrada',
        description: 'Elimina un producto de la entrada. Solo se permite si el estado de la entrada es Pendiente. Ajusta automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Entradas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalle eliminado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado de la entrada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Detalle no encontrado'),
            new OA\Response(response: 422, description: 'No se puede eliminar un producto de una entrada ya procesada'),
            new OA\Response(response: 500, description: 'Error al eliminar')
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetalleEntradaInventario::with('entradaInventario')
            ->findOrFail($id);

        if ($detalle->entradaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }
        
        $this->authorize('delete', $detalle);

        if ($detalle->entradaInventario->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un producto de una entrada ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = $detalle->subtotal;
            $detalle->entradaInventario->decrement('monto_total', $subtotal);
            $detalle->delete();

            $this->flushCache(['detalles-entradas-inventario', 'inventario', 'entradas']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de la entrada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
