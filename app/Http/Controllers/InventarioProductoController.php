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

        $query->orderBy('created_at', 'desc');

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
            'message' => 'Inventario creado exitosamente',
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
            'message' => 'Inventario actualizado exitosamente',
            'data' => new InventarioProductoResource($inventarioProducto)
        ]);
    }

    public function destroy(InventarioProducto $inventarioProducto): JsonResponse
    {
        $inventarioProducto->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Inventario eliminado exitosamente'
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

    public function porProducto(int $productoId): JsonResponse
    {
        $inventarios = InventarioProducto::where('producto_id', $productoId)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'stock_total' => $inventarios->sum('stock_actual')
        ]);
    }

    public function bajoStock(): JsonResponse
    {
        $inventarios = InventarioProducto::bajoStock()
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total_productos' => $inventarios->count()
        ]);
    }

    public function stockCritico(): JsonResponse
    {
        $inventarios = InventarioProducto::stockCritico()
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total_productos' => $inventarios->count()
        ]);
    }

    public function resumenAlmacen(int $almacenId): JsonResponse
    {
        $inventarios = InventarioProducto::where('almacen_id', $almacenId)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_productos' => $inventarios->count(),
                'valor_total_inventario' => $inventarios->sum(function ($inv) {
                    return $inv->stock_actual * $inv->costo_promedio;
                }),
                'productos_bajo_stock' => $inventarios->filter(fn($inv) => $inv->esBajoStock())->count(),
                'productos_stock_critico' => $inventarios->filter(fn($inv) => $inv->esStockCritico())->count()
            ]
        ]);
    }
}
