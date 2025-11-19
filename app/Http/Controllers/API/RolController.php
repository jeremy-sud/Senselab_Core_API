<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Http\Resources\RolResource;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para gestión de roles (RBAC)
 * 
 * Los roles definen niveles de acceso en el sistema.
 * Nota: Tabla global sin empresa_id según api_db.sql
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class RolController extends Controller
{
    /**
     * Listar todos los roles activos
     * 
     * GET /api/roles
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Rol::query()->with(['permisos']);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        $roles = $query->get();

        return RolResource::collection($roles);
    }

    /**
     * Crear un nuevo rol
     * 
     * POST /api/roles
     */
    public function store(StoreRolRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $rol = Rol::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo' => $validated['activo'] ?? true
        ]);

        // Asignar permisos si fueron proporcionados
        if (isset($validated['permisos'])) {
            $rol->permisos()->attach($validated['permisos']);
        }

        $rol->load('permisos');

        return (new RolResource($rol))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un rol específico
     * 
     * GET /api/roles/{id}
     */
    public function show(int $id): RolResource
    {
        $rol = Rol::with(['permisos', 'usuarios'])->findOrFail($id);

        return new RolResource($rol);
    }

    /**
     * Actualizar un rol existente
     * 
     * PUT/PATCH /api/roles/{id}
     */
    public function update(UpdateRolRequest $request, int $id): RolResource
    {
        $rol = Rol::findOrFail($id);
        $validated = $request->validated();

        $rol->update([
            'nombre' => $validated['nombre'] ?? $rol->nombre,
            'descripcion' => $validated['descripcion'] ?? $rol->descripcion,
            'activo' => $validated['activo'] ?? $rol->activo
        ]);

        // Sincronizar permisos si fueron proporcionados
        if (isset($validated['permisos'])) {
            $rol->permisos()->sync($validated['permisos']);
        }

        $rol->load('permisos');

        return new RolResource($rol);
    }

    /**
     * Eliminar un rol (soft delete)
     * 
     * DELETE /api/roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $rol = Rol::findOrFail($id);

        // Validar que no tenga usuarios asignados
        if ($rol->usuarios()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados'
            ], 422);
        }

        $rol->eliminado = 1;
        $rol->activo = 0;
        $rol->save();

        return response()->json([
            'message' => 'Rol eliminado exitosamente',
            'data' => new RolResource($rol)
        ], 200);
    }

    /**
     * Asignar permisos a un rol
     * 
     * POST /api/roles/{id}/permisos
     */
    public function asignarPermisos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'permisos' => ['required', 'array'],
            'permisos.*' => ['required', 'integer', 'exists:permisos,id']
        ]);

        $rol = Rol::findOrFail($id);
        $rol->permisos()->sync($request->permisos);
        $rol->load('permisos');

        return response()->json([
            'message' => 'Permisos asignados exitosamente',
            'data' => new RolResource($rol)
        ], 200);
    }
}
