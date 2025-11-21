<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoRequest;
use App\Http\Resources\EmpleadoResource;
use App\Models\Empleado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de empleados
 * 
 * Maneja el CRUD completo de empleados con:
 * - Filtrado por empresa (multi-tenant)
 * - Validación de documentos únicos
 * - Relaciones con cargos y usuarios
 * - Soft deletes
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class EmpleadoController extends Controller
{
    /**
     * Listar todos los empleados de la empresa del usuario autenticado
     * 
     * GET /api/empleados
     * Query params opcionales:
     * - activo: boolean (filtrar por estado)
     * - cargo_id: int (filtrar por cargo)
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = auth()->user()->empresa_id;
        
        $query = Empleado::where('empresa_id', $empresaId)
            ->with(['cargo']);

        // Filtro opcional por estado activo
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        // Filtro opcional por cargo
        if ($request->filled('cargo_id')) {
            $query->where('cargo_id', $request->cargo_id);
        }

        $empleados = $query->get();

        return EmpleadoResource::collection($empleados);
    }

    /**
     * Crear un nuevo empleado
     * 
     * POST /api/empleados
     * 
     * @param StoreEmpleadoRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $empleado = Empleado::create($validated);
        $empleado->load(['cargo']);

        return (new EmpleadoResource($empleado))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un empleado específico
     * 
     * GET /api/empleados/{id}
     * 
     * @param int $id
     * @return EmpleadoResource
     */
    public function show(int $id): EmpleadoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)
            ->with(['cargo'])
            ->findOrFail($id);

        return new EmpleadoResource($empleado);
    }

    /**
     * Actualizar un empleado existente
     * 
     * PUT/PATCH /api/empleados/{id}
     * 
     * @param UpdateEmpleadoRequest $request
     * @param int $id
     * @return EmpleadoResource
     */
    public function update(UpdateEmpleadoRequest $request, int $id): EmpleadoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)->findOrFail($id);

        $empleado->update($request->validated());
        $empleado->load(['cargo']);

        return new EmpleadoResource($empleado);
    }

    /**
     * Eliminar un empleado (soft delete)
     * 
     * DELETE /api/empleados/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)->findOrFail($id);

        // Soft delete personalizado
        $empleado->eliminado = 1;
        $empleado->activo = 0;
        $empleado->save();

        return response()->json([
            'message' => 'Empleado eliminado exitosamente',
            'data' => new EmpleadoResource($empleado)
        ], 200);
    }
}
