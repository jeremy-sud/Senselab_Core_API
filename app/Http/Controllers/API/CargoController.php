<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCargoRequest;
use App\Http\Requests\UpdateCargoRequest;
use App\Http\Resources\CargoResource;
use App\Models\Cargo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de cargos de empleados
 * 
 * Define posiciones laborales (Gerente, Vendedor, Contador, etc.)
 * Nota: Tabla global sin empresa_id según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class CargoController extends Controller
{
    /**
     * Listar todos los cargos
     * 
     * GET /api/cargos
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Cargo::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $cargos = $query->get();

        return CargoResource::collection($cargos);
    }

    /**
     * Crear un nuevo cargo
     * 
     * POST /api/cargos
     */
    public function store(StoreCargoRequest $request): JsonResponse
    {
        $cargo = Cargo::create($request->validated());

        return (new CargoResource($cargo))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un cargo específico
     * 
     * GET /api/cargos/{id}
     */
    public function show(int $id): CargoResource
    {
        $cargo = Cargo::with('empleados')->findOrFail($id);

        return new CargoResource($cargo);
    }

    /**
     * Actualizar un cargo existente
     * 
     * PUT/PATCH /api/cargos/{id}
     */
    public function update(UpdateCargoRequest $request, int $id): CargoResource
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->update($request->validated());

        return new CargoResource($cargo);
    }

    /**
     * Eliminar un cargo (soft delete)
     * 
     * DELETE /api/cargos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $cargo = Cargo::findOrFail($id);

        // Validar que no tenga empleados asignados
        if ($cargo->empleados()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el cargo porque tiene empleados asignados'
            ], 422);
        }

        $cargo->eliminado = 1;
        $cargo->activo = 0;
        $cargo->save();

        return response()->json([
            'message' => 'Cargo eliminado exitosamente',
            'data' => new CargoResource($cargo)
        ], 200);
    }
}
