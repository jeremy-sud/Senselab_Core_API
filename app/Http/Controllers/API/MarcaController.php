<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMarcaRequest;
use App\Http\Requests\UpdateMarcaRequest;
use App\Http\Resources\MarcaResource;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de marcas de productos
 * 
 * Nota: Las marcas son globales (sin empresa_id) según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class MarcaController extends Controller
{
    /**
     * Listar todas las marcas activas
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Marca::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $marcas = $query->get();

        return MarcaResource::collection($marcas);
    }

    /**
     * Crear una nueva marca
     */
    public function store(StoreMarcaRequest $request): JsonResponse
    {
        $marca = Marca::create($request->validated());

        return (new MarcaResource($marca))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una marca específica
     */
    public function show(int $id): MarcaResource
    {
        $marca = Marca::findOrFail($id);

        return new MarcaResource($marca);
    }

    /**
     * Actualizar una marca existente
     */
    public function update(UpdateMarcaRequest $request, int $id): MarcaResource
    {
        $marca = Marca::findOrFail($id);
        $marca->update($request->validated());

        return new MarcaResource($marca);
    }

    /**
     * Eliminar una marca (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $marca = Marca::findOrFail($id);

        $marca->eliminado = 1;
        $marca->activo = 0;
        $marca->save();

        return response()->json([
            'message' => 'Marca eliminada exitosamente',
            'data' => new MarcaResource($marca)
        ], 200);
    }
}
