<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermisoRequest;
use App\Http\Requests\UpdatePermisoRequest;
use App\Http\Resources\PermisoResource;
use App\Models\Permiso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de permisos (RBAC)
 * 
 * Los permisos definen acciones específicas en el sistema.
 * Nota: Tabla global sin empresa_id según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class PermisoController extends Controller
{
    /**
     * Listar todos los permisos
     * 
     * GET /api/permisos
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Permiso::query();

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        $permisos = $query->get();

        return PermisoResource::collection($permisos);
    }

    /**
     * Crear un nuevo permiso
     * 
     * POST /api/permisos
     */
    public function store(StorePermisoRequest $request): JsonResponse
    {
        $permiso = Permiso::create($request->validated());

        return (new PermisoResource($permiso))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un permiso específico
     * 
     * GET /api/permisos/{id}
     */
    public function show(int $id): PermisoResource
    {
        $permiso = Permiso::with('roles')->findOrFail($id);

        return new PermisoResource($permiso);
    }

    /**
     * Actualizar un permiso existente
     * 
     * PUT/PATCH /api/permisos/{id}
     */
    public function update(UpdatePermisoRequest $request, int $id): PermisoResource
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->update($request->validated());

        return new PermisoResource($permiso);
    }

    /**
     * Eliminar un permiso (soft delete)
     * 
     * DELETE /api/permisos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $permiso = Permiso::findOrFail($id);

        // Validar que no esté asignado a ningún rol
        if ($permiso->roles()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el permiso porque está asignado a uno o más roles'
            ], 422);
        }

        $permiso->eliminado = 1;
        $permiso->activo = 0;
        $permiso->save();

        return response()->json([
            'message' => 'Permiso eliminado exitosamente',
            'data' => new PermisoResource($permiso)
        ], 200);
    }

    /**
     * Obtener todos los módulos disponibles
     * 
     * GET /api/permisos/modulos
     */
    public function modulos(): JsonResponse
    {
        $modulos = Permiso::select('modulo')
            ->whereNotNull('modulo')
            ->distinct()
            ->pluck('modulo');

        return response()->json([
            'data' => $modulos
        ], 200);
    }
}
