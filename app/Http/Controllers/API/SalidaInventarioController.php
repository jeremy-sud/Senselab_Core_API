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
    public function index(Request $request): JsonResponse
    {
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
    public function store(StoreSalidaInventarioRequest $request): JsonResponse
    {
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
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'cliente', 'proveedor', 'venta', 'detalles.producto.unidadMedida'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SalidaInventarioResource($salida)
        ]);
    }

    /**
     * Actualizar salida de inventario
     */
    public function update(UpdateSalidaInventarioRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);

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
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);

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
