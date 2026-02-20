<?php

namespace App\Http\Controllers;

use App\Models\RolPermiso;
use App\Http\Requests\StoreRolPermisoRequest;
use App\Http\Requests\UpdateRolPermisoRequest;
use App\Http\Resources\RolPermisoResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AsignarPermisosRequest;
use App\Http\Requests\RemoverPermisosRequest;
use App\Http\Requests\SincronizarPermisosRequest;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Rol-Permiso',
    description: 'Gestión de permisos asignados a roles (control de acceso)'
)]
class RolPermisoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
        #[OA\Get(
        path: '/api/rol-permiso',
        summary: 'Listar permisos de roles',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $query = RolPermiso::query();

        // Filtros
        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        if ($request->filled('permiso_id')) {
            $query->where('permiso_id', $request->permiso_id);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Usar paginate() normal aquí: tabla pivot pequeña, necesita total count
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
        #[OA\Post(
        path: '/api/rol-permiso',
        summary: 'Asignar permiso a rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]

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
     * Soporta ambos modos: por RolPermiso ID o por rol_id + permiso_id
     */
        #[OA\Delete(
        path: '/api/rol-permiso/{id}',
        summary: 'Quitar permiso de rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]

    public function destroy(int $rol, int $permiso): JsonResponse
    {
        $rolPermiso = RolPermiso::where('rol_id', $rol)
            ->where('permiso_id', $permiso)
            ->first();

        if (!$rolPermiso) {
            return response()->json([
                'success' => false,
                'message' => 'La relación rol-permiso no existe'
            ], 404);
        }

        $rolPermiso->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permiso removido del rol exitosamente'
        ]);
    }

    /**
     * Asignar múltiples permisos a un rol.
     */
    public function asignarPermisos(AsignarPermisosRequest $request): JsonResponse
    {

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
    public function removerPermisos(RemoverPermisosRequest $request): JsonResponse
    {

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
            ->where('activo', 1)
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
            ->where('activo', 1)
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($roles)
        ]);
    }

    /**
     * Sincronizar permisos de un rol (reemplaza todos los existentes).
     */
    public function sincronizarPermisos(SincronizarPermisosRequest $request): JsonResponse
    {

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
