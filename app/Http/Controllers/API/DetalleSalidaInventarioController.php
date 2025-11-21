<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDetalleSalidaInventarioRequest;
use App\Http\Requests\UpdateDetalleSalidaInventarioRequest;
use App\Http\Resources\DetalleSalidaInventarioResource;
use App\Models\DetalleSalidaInventario;
use App\Models\SalidaInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador para Detalle de Salidas de Inventario
 * 
 * Gestiona los productos específicos incluidos en cada salida de inventario.
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleSalidaInventarioController extends Controller
{
    /**
     * Listar detalles de una salida específica
     */
    #[OA\Get(
        path: '/api/salidas-inventario/{salidaId}/detalles',
        summary: 'Listar detalles de una salida',
        description: 'Obtiene todos los productos incluidos en una salida de inventario específica',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'salidaId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detalles de la salida',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DetalleSalidaInventario'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Salida no encontrada')
        ]
    )]
    public function index(Request $request, int $salidaId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($salidaId);

        $detalles = DetalleSalidaInventario::where('salida_inventario_id', $salidaId)
            ->with(['producto.unidadMedida', 'producto.categoriaProducto'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => DetalleSalidaInventarioResource::collection($detalles)
        ]);
    }

    /**
     * Agregar producto a salida de inventario
     */
    #[OA\Post(
        path: '/api/detalles-salidas-inventario',
        summary: 'Agregar producto a salida',
        description: 'Agrega un producto a una salida de inventario. Solo se permite si el estado de la salida es Pendiente. Actualiza automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['salida_inventario_id', 'producto_id', 'cantidad', 'costo_unitario_salida'],
                properties: [
                    new OA\Property(property: 'salida_inventario_id', type: 'integer', example: 1),
                    new OA\Property(property: 'producto_id', type: 'integer', example: 15),
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 25.00),
                    new OA\Property(property: 'costo_unitario_salida', type: 'number', format: 'decimal', example: 30.00),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Producto agregado a la salida exitosamente'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleSalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'No se pueden agregar productos a una salida ya procesada'),
            new OA\Response(response: 500, description: 'Error al agregar el producto')
        ]
    )]
    public function store(StoreDetalleSalidaInventarioRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->findOrFail($request->salida_inventario_id);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden agregar productos a una salida ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = $request->cantidad * $request->costo_unitario_salida;

            $detalle = DetalleSalidaInventario::create([
                'salida_inventario_id' => $request->salida_inventario_id,
                'producto_id' => $request->producto_id,
                'cantidad' => $request->cantidad,
                'costo_unitario_salida' => $request->costo_unitario_salida,
                'subtotal' => $subtotal,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento
            ]);

            // Actualizar monto total de la salida
            $salida->increment('monto_total', $subtotal);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado a la salida exitosamente',
                'data' => new DetalleSalidaInventarioResource($detalle->load('producto'))
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
        path: '/api/detalles-salidas-inventario/{id}',
        summary: 'Obtener detalle de salida',
        description: 'Retorna la información de un detalle específico de salida de inventario',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleSalidaInventario')
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
        $detalle = DetalleSalidaInventario::with(['producto', 'salidaInventario'])
            ->findOrFail($id);

        $empresaId = $request->user()->empresa_id;
        if ($detalle->salidaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new DetalleSalidaInventarioResource($detalle)
        ]);
    }

    /**
     * Actualizar detalle de salida
     */
    #[OA\Put(
        path: '/api/detalles-salidas-inventario/{id}',
        summary: 'Actualizar detalle de salida',
        description: 'Modifica un detalle de salida. Solo se permite si el estado de la salida es Pendiente. Ajusta automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cantidad', 'costo_unitario_salida'],
                properties: [
                    new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', example: 30.00),
                    new OA\Property(property: 'costo_unitario_salida', type: 'number', format: 'decimal', example: 32.00),
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/DetalleSalidaInventario')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Detalle no encontrado'),
            new OA\Response(response: 422, description: 'No se puede modificar una salida ya procesada'),
            new OA\Response(response: 500, description: 'Error al actualizar')
        ]
    )]
    public function update(UpdateDetalleSalidaInventarioRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetalleSalidaInventario::with('salidaInventario')
            ->findOrFail($id);

        if ($detalle->salidaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->salidaInventario->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una salida ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotalAnterior = $detalle->subtotal;
            $nuevoSubtotal = $request->cantidad * $request->costo_unitario_salida;

            $detalle->update([
                'cantidad' => $request->cantidad,
                'costo_unitario_salida' => $request->costo_unitario_salida,
                'subtotal' => $nuevoSubtotal,
                'lote' => $request->lote,
                'fecha_vencimiento' => $request->fecha_vencimiento
            ]);

            // Ajustar monto total de la salida
            $diferencia = $nuevoSubtotal - $subtotalAnterior;
            $detalle->salidaInventario->increment('monto_total', $diferencia);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detalle actualizado exitosamente',
                'data' => new DetalleSalidaInventarioResource($detalle->fresh('producto'))
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
     * Eliminar producto de la salida
     */
    #[OA\Delete(
        path: '/api/detalles-salidas-inventario/{id}',
        summary: 'Eliminar detalle de salida',
        description: 'Elimina un producto de la salida. Solo se permite si el estado de la salida es Pendiente. Ajusta automáticamente el monto_total',
        security: [['sanctum' => []]],
        tags: ['Inventario - Salidas'],
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
                        new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado de la salida exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Detalle no encontrado'),
            new OA\Response(response: 422, description: 'No se puede eliminar un producto de una salida ya procesada'),
            new OA\Response(response: 500, description: 'Error al eliminar')
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetalleSalidaInventario::with('salidaInventario')
            ->findOrFail($id);

        if ($detalle->salidaInventario->empresa_id !== $empresaId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if ($detalle->salidaInventario->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un producto de una salida ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $subtotal = $detalle->subtotal;
            $detalle->salidaInventario->decrement('monto_total', $subtotal);
            $detalle->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de la salida exitosamente'
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
