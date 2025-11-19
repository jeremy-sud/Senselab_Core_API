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
