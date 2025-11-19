<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaProductoRequest;
use App\Http\Requests\UpdateCategoriaProductoRequest;
use App\Http\Resources\CategoriaProductoResource;
use App\Models\CategoriaProducto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de categorías de productos
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class CategoriaProductoController extends Controller
{
    /**
     * Listar todas las categorías de la empresa
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = auth()->user()->empresa_id;
        
        $query = CategoriaProducto::where('empresa_id', $empresaId);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $categorias = $query->get();

        return CategoriaProductoResource::collection($categorias);
    }

    /**
     * Crear una nueva categoría
     */
    public function store(StoreCategoriaProductoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::create($validated);

        return (new CategoriaProductoResource($categoria))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una categoría específica
     */
    public function show(int $id): CategoriaProductoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)
            ->findOrFail($id);

        return new CategoriaProductoResource($categoria);
    }

    /**
     * Actualizar una categoría existente
     */
    public function update(UpdateCategoriaProductoRequest $request, int $id): CategoriaProductoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)->findOrFail($id);
        $categoria->update($request->validated());

        return new CategoriaProductoResource($categoria);
    }

    /**
     * Eliminar una categoría (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $categoria = CategoriaProducto::where('empresa_id', $empresaId)->findOrFail($id);

        $categoria->eliminado = 1;
        $categoria->activo = 0;
        $categoria->save();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente',
            'data' => new CategoriaProductoResource($categoria)
        ], 200);
    }
}
