<?php

namespace App\Http\Controllers;

use App\Models\RolUsuario;
use App\Http\Requests\StoreRolUsuarioRequest;
use App\Http\Requests\UpdateRolUsuarioRequest;
use App\Http\Resources\RolUsuarioResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AsignarRolesUsuarioRequest;

class RolUsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RolUsuario::where('activo', 1)->where('eliminado', 0);

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->rol_id);
        }

        $rolesUsuarios = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($rolesUsuarios),
            'meta' => [
                'current_page' => $rolesUsuarios->currentPage(),
                'last_page' => $rolesUsuarios->lastPage(),
                'per_page' => $rolesUsuarios->perPage(),
                'total' => $rolesUsuarios->total(),
            ]
        ]);
    }

    public function store(StoreRolUsuarioRequest $request): JsonResponse
    {
        $rolUsuario = RolUsuario::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Rol asignado al usuario exitosamente',
            'data' => new RolUsuarioResource($rolUsuario)
        ], 201);
    }

    public function show(RolUsuario $rolUsuario): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new RolUsuarioResource($rolUsuario)
        ]);
    }

    public function update(UpdateRolUsuarioRequest $request, RolUsuario $rolUsuario): JsonResponse
    {
        $rolUsuario->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asignación de rol actualizada exitosamente',
            'data' => new RolUsuarioResource($rolUsuario)
        ]);
    }

    public function destroy(RolUsuario $rolUsuario): JsonResponse
    {
        $rolUsuario->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Asignación de rol eliminada exitosamente'
        ]);
    }

    public function rolesPorUsuario(int $usuarioId): JsonResponse
    {
        $roles = RolUsuario::where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($roles)
        ]);
    }

    public function usuariosPorRol(int $rolId): JsonResponse
    {
        $usuarios = RolUsuario::where('rol_id', $rolId)
            ->where('activo', 1)
            ->where('eliminado', 0)
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($usuarios)
        ]);
    }

    public function asignarRoles(AsignarRolesUsuarioRequest $request): JsonResponse
    {

        // Desactivar roles actuales
        RolUsuario::where('usuario_id', $request->usuario_id)
            ->update(['activo' => 0]);

        // Asignar nuevos roles
        $rolesAsignados = [];
        foreach ($request->roles as $rolId) {
            $rolUsuario = RolUsuario::updateOrCreate(
                [
                    'usuario_id' => $request->usuario_id,
                    'rol_id' => $rolId,
                ],
                [
                    'activo' => 1,
                    'eliminado' => 0,
                ]
            );
            $rolesAsignados[] = $rolUsuario;
        }

        return response()->json([
            'success' => true,
            'message' => 'Roles asignados exitosamente',
            'data' => RolUsuarioResource::collection($rolesAsignados)
        ]);
    }
}
