<?php

namespace App\Http\Controllers;

use App\Models\RolPermiso;
use App\Http\Requests\StoreRolPermisoRequest;
use App\Http\Requests\UpdateRolPermisoRequest;
use App\Http\Resources\RolPermisoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RolPermisoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RolPermiso::with(['rol', 'permiso']);

        // Filtros
        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->filled('permiso_id')) {
            $query->where('permiso_id', $request->permiso_id);
        }

        $rolesPermisos = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($rolesPermisos),
            'meta' => [
                'current_page' => $rolesPermisos->currentPage(),
                'last_page' => $rolesPermisos->lastPage(),
                'per_page' => $rolesPermisos->perPage(),
                'total' => $rolesPermisos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRolPermisoRequest $request): JsonResponse
    {
        // Verificar que no exista ya la relación
        $existente = RolPermiso::where('rol_id', $request->rol_id)
            ->where('permiso_id', $request->permiso_id)
            ->first();

        if ($existente) {
            return response()->json([
                'success' => false,
                'message' => 'Este permiso ya está asignado al rol'
            ], 422);
        }

        $rolPermiso = RolPermiso::create([
            'rol_id' => $request->rol_id,
            'permiso_id' => $request->permiso_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permiso asignado al rol exitosamente',
            'data' => new RolPermisoResource($rolPermiso)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(RolPermiso $rolPermiso): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RolPermisoResource($rolPermiso)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRolPermisoRequest $request, RolPermiso $rolPermiso): JsonResponse
    {
        $rolPermiso->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Relación rol-permiso actualizada exitosamente',
            'data' => new RolPermisoResource($rolPermiso)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RolPermiso $rolPermiso): JsonResponse
    {
        $rolPermiso->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permiso removido del rol exitosamente'
        ]);
    }

    /**
     * Asignar múltiples permisos a un rol.
     */
    public function asignarPermisos(Request $request): JsonResponse
    {
        $request->validate([
            'rol_id' => 'required|integer|exists:roles,id',
            'permiso_ids' => 'required|array|min:1',
            'permiso_ids.*' => 'required|integer|exists:permisos,id',
        ]);

        $asignados = [];
        $yaExistentes = [];

        foreach ($request->permiso_ids as $permisoId) {
            $existente = RolPermiso::where('rol_id', $request->rol_id)
                ->where('permiso_id', $permisoId)
                ->first();

            if ($existente) {
                $yaExistentes[] = $permisoId;
            } else {
                $rolPermiso = RolPermiso::create([
                    'rol_id' => $request->rol_id,
                    'permiso_id' => $permisoId,
                ]);
                $asignados[] = new RolPermisoResource($rolPermiso);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($asignados) . ' permiso(s) asignado(s) exitosamente',
            'data' => [
                'asignados' => $asignados,
                'ya_existentes' => $yaExistentes,
            ]
        ], 201);
    }

    /**
     * Remover múltiples permisos de un rol.
     */
    public function removerPermisos(Request $request): JsonResponse
    {
        $request->validate([
            'rol_id' => 'required|integer|exists:roles,id',
            'permiso_ids' => 'required|array|min:1',
            'permiso_ids.*' => 'required|integer',
        ]);

        $removidos = RolPermiso::where('rol_id', $request->rol_id)
            ->whereIn('permiso_id', $request->permiso_ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$removidos} permiso(s) removido(s) exitosamente"
        ]);
    }

    /**
     * Obtener todos los permisos de un rol.
     */
    public function permisosPorRol(int $rolId): JsonResponse
    {
        $permisos = RolPermiso::where('rol_id', $rolId)
            ->with('permiso')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($permisos)
        ]);
    }

    /**
     * Obtener todos los roles que tienen un permiso específico.
     */
    public function rolesPorPermiso(int $permisoId): JsonResponse
    {
        $roles = RolPermiso::where('permiso_id', $permisoId)
            ->with('rol')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($roles)
        ]);
    }

    /**
     * Sincronizar permisos de un rol (reemplaza todos los existentes).
     */
    public function sincronizarPermisos(Request $request): JsonResponse
    {
        $request->validate([
            'rol_id' => 'required|integer|exists:roles,id',
            'permiso_ids' => 'required|array',
            'permiso_ids.*' => 'required|integer|exists:permisos,id',
        ]);

        // Remover todos los permisos actuales del rol
        RolPermiso::where('rol_id', $request->rol_id)->delete();

        // Asignar los nuevos permisos
        $nuevosPermisos = [];
        foreach ($request->permiso_ids as $permisoId) {
            $rolPermiso = RolPermiso::create([
                'rol_id' => $request->rol_id,
                'permiso_id' => $permisoId,
            ]);
            $nuevosPermisos[] = new RolPermisoResource($rolPermiso);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permisos sincronizados exitosamente',
            'data' => $nuevosPermisos
        ]);
    }
}
