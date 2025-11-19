<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnidadMedidaRequest;
use App\Http\Requests\UpdateUnidadMedidaRequest;
use App\Http\Resources\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de unidades de medida
 * 
 * Nota: Las unidades de medida son globales (sin empresa_id) según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class UnidadMedidaController extends Controller
{
    /**
     * Listar todas las unidades de medida
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UnidadMedida::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $unidades = $query->get();

        return UnidadMedidaResource::collection($unidades);
    }

    /**
     * Crear una nueva unidad de medida
     */
    public function store(StoreUnidadMedidaRequest $request): JsonResponse
    {
        $unidad = UnidadMedida::create($request->validated());

        return (new UnidadMedidaResource($unidad))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar una unidad de medida específica
     */
    public function show(int $id): UnidadMedidaResource
    {
        $unidad = UnidadMedida::findOrFail($id);

        return new UnidadMedidaResource($unidad);
    }

    /**
     * Actualizar una unidad de medida existente
     */
    public function update(UpdateUnidadMedidaRequest $request, int $id): UnidadMedidaResource
    {
        $unidad = UnidadMedida::findOrFail($id);
        $unidad->update($request->validated());

        return new UnidadMedidaResource($unidad);
    }

    /**
     * Eliminar una unidad de medida (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $unidad = UnidadMedida::findOrFail($id);

        $unidad->eliminado = 1;
        $unidad->activo = 0;
        $unidad->save();

        return response()->json([
            'message' => 'Unidad de medida eliminada exitosamente',
            'data' => new UnidadMedidaResource($unidad)
        ], 200);
    }
}
