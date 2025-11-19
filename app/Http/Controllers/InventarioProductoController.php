<?php

namespace App\Http\Controllers;

use App\Models\InventarioProducto;
use App\Http\Requests\StoreInventarioProductoRequest;
use App\Http\Requests\UpdateInventarioProductoRequest;
use App\Http\Resources\InventarioProductoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InventarioProductoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = InventarioProducto::where('eliminado', 0);

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        $query->orderBy('stock_actual', 'asc');

        $inventarios = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'meta' => [
                'current_page' => $inventarios->currentPage(),
                'last_page' => $inventarios->lastPage(),
                'per_page' => $inventarios->perPage(),
                'total' => $inventarios->total(),
            ]
        ]);
    }

    public function store(StoreInventarioProductoRequest $request): JsonResponse
    {
        $inventario = InventarioProducto::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventario de producto creado exitosamente',
            'data' => new InventarioProductoResource($inventario)
        ], 201);
    }

    public function show(InventarioProducto $inventarioProducto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new InventarioProductoResource($inventarioProducto)
        ]);
    }

    public function update(UpdateInventarioProductoRequest $request, InventarioProducto $inventarioProducto): JsonResponse
    {
        $inventarioProducto->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventario de producto actualizado exitosamente',
            'data' => new InventarioProductoResource($inventarioProducto)
        ]);
    }

    public function destroy(InventarioProducto $inventarioProducto): JsonResponse
    {
        $inventarioProducto->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Inventario de producto eliminado exitosamente'
        ]);
    }

    public function porAlmacen(int $almacenId): JsonResponse
    {
        $inventarios = InventarioProducto::where('almacen_id', $almacenId)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios)
        ]);
    }

    public function bajoStockMinimo(): JsonResponse
    {
        $inventarios = InventarioProducto::bajoStockMinimo()
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total' => $inventarios->count()
        ]);
    }

    public function sobreStockMaximo(): JsonResponse
    {
        $inventarios = InventarioProducto::sobreStockMaximo()
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total' => $inventarios->count()
        ]);
    }

    public function resumenPorAlmacen(): JsonResponse
    {
        $resumen = InventarioProducto::where('eliminado', 0)
            ->selectRaw('almacen_id, count(*) as total_productos, sum(stock_actual) as stock_total, avg(costo_promedio) as costo_promedio')
            ->groupBy('almacen_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
