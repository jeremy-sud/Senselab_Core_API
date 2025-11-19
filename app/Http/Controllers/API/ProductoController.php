<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = $request->input('empresa_id');
            $categoriaId = $request->input('categoria_id');
            $tipo = $request->input('tipo');
            
            $query = Producto::with([
                'empresa',
                'categoria',
                'unidadMedida',
                'marca',
                'impuesto'
            ]);
            
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('codigo', 'like', "%{$search}%")
                      ->orWhere('codigo_barras', 'like', "%{$search}%")
                      ->orWhere('descripcion', 'like', "%{$search}%");
                });
            }
            
            if ($empresaId) {
                $query->porEmpresa($empresaId);
            }
            
            if ($categoriaId) {
                $query->porCategoria($categoriaId);
            }
            
            if ($tipo) {
                $query->porTipo($tipo);
            }
            
            if ($request->boolean('activos')) {
                $query->activos();
            }
            
            $productos = $query->orderBy('nombre', 'asc')
                               ->paginate($perPage);
            
            return ProductoResource::collection($productos);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreProductoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductoRequest $request)
    {
        try {
            $producto = Producto::create($request->validated());
            $producto->load([
                'empresa',
                'categoria',
                'unidadMedida',
                'marca',
                'impuesto'
            ]);
            
            return (new ProductoResource($producto))
                ->additional(['message' => 'Producto creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        try {
            $producto = Producto::with([
                'empresa',
                'categoria',
                'unidadMedida',
                'marca',
                'proveedorPredeterminado',
                'impuesto',
                'cabys'
            ])->findOrFail($id);
            
            return new ProductoResource($producto);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateProductoRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductoRequest $request, int $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            
            $producto->update($request->validated());
            $producto->load([
                'empresa',
                'categoria',
                'unidadMedida',
                'marca',
                'impuesto'
            ]);
            
            return (new ProductoResource($producto))
                ->additional(['message' => 'Producto actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            
            // Soft delete - marcar como inactivo
            $producto->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
