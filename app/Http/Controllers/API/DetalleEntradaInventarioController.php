<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDetalleEntradaInventarioRequest;
use App\Http\Requests\UpdateDetalleEntradaInventarioRequest;
use App\Http\Resources\DetalleEntradaInventarioResource;
use App\Models\DetalleEntradaInventario;
use App\Models\EntradaInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    /**
     * Listar detalles de una entrada específica
     */
    public function index(Request $request, int $entradaId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($entradaId);

        $detalles = DetalleEntradaInventario::where('entrada_inventario_id', $entradaId)
            ->with(['producto.unidadMedida', 'producto.categoriaProducto'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => DetalleEntradaInventarioResource::collection($detalles)
        ]);
    }

    /**
     * Agregar producto a entrada de inventario
     */
    public function store(StoreDetalleEntradaInventarioRequest $request): JsonResponse
    {
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

        return response()->json([
            'success' => true,
            'data' => new DetalleEntradaInventarioResource($detalle)
        ]);
    }

    /**
     * Actualizar detalle de entrada
     */
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
