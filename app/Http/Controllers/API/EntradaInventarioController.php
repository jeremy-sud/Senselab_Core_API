<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Http\Requests\UpdateEntradaInventarioRequest;
use App\Http\Resources\EntradaInventarioResource;
use App\Models\EntradaInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    /**
     * Listar todas las entradas de inventario de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entradas = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'proveedor', 'ordenCompra', 'detalles.producto'])
            ->orderBy('fecha_entrada', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => EntradaInventarioResource::collection($entradas),
            'meta' => [
                'current_page' => $entradas->currentPage(),
                'total' => $entradas->total(),
                'per_page' => $entradas->perPage()
            ]
        ]);
    }

    /**
     * Crear nueva entrada de inventario
     */
    public function store(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();
        try {
            $entrada = EntradaInventario::create([
                'empresa_id' => $empresaId,
                'almacen_id' => $request->almacen_id,
                'fecha_entrada' => $request->fecha_entrada,
                'tipo_entrada' => $request->tipo_entrada,
                'orden_compra_id' => $request->orden_compra_id,
                'proveedor_id' => $request->proveedor_id,
                'documento_referencia' => $request->documento_referencia,
                'observaciones' => $request->observaciones,
                'estado' => 'Pendiente',
                'monto_total' => 0
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada de inventario creada exitosamente',
                'data' => new EntradaInventarioResource($entrada->load(['almacen', 'proveedor']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la entrada de inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una entrada específica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'proveedor', 'ordenCompra', 'detalles.producto.unidadMedida'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new EntradaInventarioResource($entrada)
        ]);
    }

    /**
     * Actualizar entrada de inventario
     */
    public function update(UpdateEntradaInventarioRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar una entrada ya procesada'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $entrada->update($request->only([
                'almacen_id',
                'fecha_entrada',
                'tipo_entrada',
                'orden_compra_id',
                'proveedor_id',
                'documento_referencia',
                'observaciones'
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Entrada de inventario actualizada exitosamente',
                'data' => new EntradaInventarioResource($entrada->load(['almacen', 'proveedor']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la entrada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar entrada de inventario
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una entrada ya procesada'
            ], 422);
        }

        $entrada->delete();

        return response()->json([
            'success' => true,
            'message' => 'Entrada de inventario eliminada exitosamente'
        ]);
    }

    /**
     * Procesar entrada de inventario (actualiza stock)
     */
    public function procesar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->with('detalles.producto')
            ->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'success' => false,
                'message' => 'La entrada ya fue procesada anteriormente'
            ], 422);
        }

        if ($entrada->detalles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede procesar una entrada sin productos'
            ], 422);
        }

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
                        ->increment('cantidad_actual', $detalle->cantidad);
                } else {
                    DB::table('inventarios')->insert([
                        'producto_id' => $detalle->producto_id,
                        'almacen_id' => $entrada->almacen_id,
                        'cantidad_actual' => $detalle->cantidad,
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
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la entrada',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar entrada de inventario
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

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
    public function porProveedor(Request $request, int $proveedorId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

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
    public function porAlmacen(Request $request, int $almacenId): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

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
    public function resumenPorTipo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

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
    public function pendientes(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

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
