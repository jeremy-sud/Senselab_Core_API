<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEntradaInventarioRequest;
use App\Http\Requests\StoreSalidaInventarioRequest;
use App\Http\Resources\EntradaInventarioResource;
use App\Http\Resources\SalidaInventarioResource;
use App\Models\EntradaInventario;
use App\Models\SalidaInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de movimientos de inventario
 * 
 * Maneja tanto entradas como salidas de inventario con:
 * - Filtrado por almacén, tipo de movimiento y fechas
 * - Control de estados (Pendiente, Procesada, Cancelada)
 * - Trazabilidad completa
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class InventarioController extends Controller
{
    /**
     * Listar todas las entradas de inventario
     * 
     * GET /api/inventario/entradas
     */
    public function indexEntradas(Request $request): AnonymousResourceCollection
    {
        $empresaId = auth()->user()->empresa_id;
        
        $query = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles']);

        // Filtros opcionales
        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_entrada')) {
            $query->where('tipo_entrada', $request->tipo_entrada);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_hasta);
        }

        $entradas = $query->orderBy('fecha_entrada', 'desc')->get();

        return EntradaInventarioResource::collection($entradas);
    }

    /**
     * Crear una nueva entrada de inventario
     * 
     * POST /api/inventario/entradas
     */
    public function storeEntrada(StoreEntradaInventarioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $entrada = EntradaInventario::create($validated);
        $entrada->load(['almacen', 'ordenCompra', 'proveedor', 'detalles']);

        return (new EntradaInventarioResource($entrada))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una entrada específica
     * 
     * GET /api/inventario/entradas/{id}
     */
    public function showEntrada(int $id): EntradaInventarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'ordenCompra', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);

        return new EntradaInventarioResource($entrada);
    }

    /**
     * Listar todas las salidas de inventario
     * 
     * GET /api/inventario/salidas
     */
    public function indexSalidas(Request $request): AnonymousResourceCollection
    {
        $empresaId = auth()->user()->empresa_id;
        
        $query = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);

        // Filtros opcionales
        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_salida')) {
            $query->where('tipo_salida', $request->tipo_salida);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_hasta);
        }

        $salidas = $query->orderBy('fecha_salida', 'desc')->get();

        return SalidaInventarioResource::collection($salidas);
    }

    /**
     * Crear una nueva salida de inventario
     * 
     * POST /api/inventario/salidas
     */
    public function storeSalida(StoreSalidaInventarioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $salida = SalidaInventario::create($validated);
        $salida->load(['almacen', 'venta', 'cliente', 'proveedor', 'detalles']);

        return (new SalidaInventarioResource($salida))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una salida específica
     * 
     * GET /api/inventario/salidas/{id}
     */
    public function showSalida(int $id): SalidaInventarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)
            ->with(['almacen', 'venta', 'cliente', 'proveedor', 'detalles.producto'])
            ->findOrFail($id);

        return new SalidaInventarioResource($salida);
    }

    /**
     * Cancelar una entrada de inventario
     * 
     * POST /api/inventario/entradas/{id}/cancelar
     */
    public function cancelarEntrada(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $entrada = EntradaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($entrada->estado === 'Procesada') {
            return response()->json([
                'message' => 'No se puede cancelar una entrada ya procesada'
            ], 422);
        }

        $entrada->estado = 'Cancelada';
        $entrada->save();

        return response()->json([
            'message' => 'Entrada de inventario cancelada exitosamente',
            'data' => new EntradaInventarioResource($entrada)
        ], 200);
    }

    /**
     * Cancelar una salida de inventario
     * 
     * POST /api/inventario/salidas/{id}/cancelar
     */
    public function cancelarSalida(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $salida = SalidaInventario::where('empresa_id', $empresaId)->findOrFail($id);

        if ($salida->estado === 'Procesada') {
            return response()->json([
                'message' => 'No se puede cancelar una salida ya procesada'
            ], 422);
        }

        $salida->estado = 'Cancelada';
        $salida->save();

        return response()->json([
            'message' => 'Salida de inventario cancelada exitosamente',
            'data' => new SalidaInventarioResource($salida)
        ], 200);
    }
}
