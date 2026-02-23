<?php

namespace App\Http\Controllers;

use App\Models\InventarioProducto;
use App\Http\Requests\StoreInventarioProductoRequest;
use App\Http\Requests\UpdateInventarioProductoRequest;
use App\Http\Resources\InventarioProductoResource;
use App\Models\Almacen;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Inventario de Productos',
    description: 'Gestión de inventario y existencias de productos por sucursal'
)]
class InventarioProductoController extends Controller
{
    use HasEmpresaContext;
        #[OA\Get(
        path: '/api/inventario-producto',
        summary: 'Listar inventario de productos',
        security: [['sanctum' => []]],
        tags: ['Inventario de Productos'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        // Multi-tenancy: uso centralizado del trait
        $empresaId = $this->getEmpresaId();
        
        $query = InventarioProducto::with(['almacen', 'producto'])
            ->where('eliminado', 0)
            ->whereHas('almacen', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

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

        #[OA\Post(
        path: '/api/inventario-producto',
        summary: 'Crear registro de inventario',
        security: [['sanctum' => []]],
        tags: ['Inventario de Productos'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]


    public function store(StoreInventarioProductoRequest $request): JsonResponse
    {
        $inventario = InventarioProducto::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inventario de producto creado exitosamente',
            'data' => new InventarioProductoResource($inventario)
        ], 201);
    }

        #[OA\Get(
        path: '/api/inventario-producto/{id}',
        summary: 'Obtener inventario de producto',
        security: [['sanctum' => []]],
        tags: ['Inventario de Productos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]


    public function show(InventarioProducto $inventarioProducto): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new InventarioProductoResource($inventarioProducto)
        ]);
    }

        #[OA\Put(
        path: '/api/inventario-producto/{id}',
        summary: 'Actualizar inventario',
        security: [['sanctum' => []]],
        tags: ['Inventario de Productos'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]


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
        $empresaId = $this->getEmpresaId();

        // Validar que el almacén pertenece a la empresa del usuario
        Almacen::where('empresa_id', $empresaId)->findOrFail($almacenId);

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
        $empresaId = $this->getEmpresaId();

        $inventarios = InventarioProducto::bajoStockMinimo()
            ->where('eliminado', 0)
            ->whereHas('almacen', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total' => $inventarios->count()
        ]);
    }

    public function sobreStockMaximo(): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $inventarios = InventarioProducto::sobreStockMaximo()
            ->where('eliminado', 0)
            ->whereHas('almacen', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => InventarioProductoResource::collection($inventarios),
            'total' => $inventarios->count()
        ]);
    }

    public function resumenPorAlmacen(): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $resumen = InventarioProducto::where('eliminado', 0)
            ->whereHas('almacen', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->selectRaw('almacen_id, count(*) as total_productos, sum(stock_actual) as stock_total, avg(costo_promedio) as costo_promedio')
            ->groupBy('almacen_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
