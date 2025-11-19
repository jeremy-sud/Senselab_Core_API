<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador API para gestión de usuarios del sistema
 * 
 * Gestiona cuentas de acceso, autenticación y asignación de roles.
 * Multi-tenant por empresa_id
 * 
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class UsuarioController extends Controller
{
    /**
     * Listar todos los usuarios de la empresa
     * 
     * GET /api/usuarios
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = auth()->user()->empresa_id;
        
        $query = Usuario::where('empresa_id', $empresaId)
            ->with(['roles', 'cargo', 'empresa']);

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('cargo_id')) {
            $query->where('cargo_id', $request->cargo_id);
        }

        $usuarios = $query->get();

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Crear un nuevo usuario
     * 
     * POST /api/usuarios
     */
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;
        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']);

        $usuario = Usuario::create($validated);

        // Asignar roles si fueron proporcionados
        if (isset($validated['roles'])) {
            $usuario->roles()->attach($validated['roles']);
        }

        $usuario->load(['roles', 'cargo', 'empresa']);

        return (new UsuarioResource($usuario))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un usuario específico
     * 
     * GET /api/usuarios/{id}
     */
    public function show(int $id): UsuarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)
            ->with(['roles.permisos', 'cargo', 'empresa', 'empleado'])
            ->findOrFail($id);

        return new UsuarioResource($usuario);
    }

    /**
     * Actualizar un usuario existente
     * 
     * PUT/PATCH /api/usuarios/{id}
     */
    public function update(UpdateUsuarioRequest $request, int $id): UsuarioResource
    {
        $empresaId = auth()->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);
        $validated = $request->validated();

        // Si se proporciona nueva contraseña, hashearla
        if (isset($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
            unset($validated['password']);
        }

        $usuario->update($validated);

        // Sincronizar roles si fueron proporcionados
        if (isset($validated['roles'])) {
            $usuario->roles()->sync($validated['roles']);
        }

        $usuario->load(['roles', 'cargo', 'empresa']);

        return new UsuarioResource($usuario);
    }

    /**
     * Eliminar un usuario (soft delete)
     * 
     * DELETE /api/usuarios/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        // No permitir que el usuario se elimine a sí mismo
        if ($usuario->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de usuario'
            ], 422);
        }

        $usuario->eliminado = 1;
        $usuario->activo = 0;
        $usuario->save();

        // Revocar todos los tokens de acceso
        $usuario->tokens()->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente',
            'data' => new UsuarioResource($usuario)
        ], 200);
    }

    /**
     * Asignar roles a un usuario
     * 
     * POST /api/usuarios/{id}/roles
     */
    public function asignarRoles(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'integer', 'exists:roles,id']
        ]);

        $empresaId = auth()->user()->empresa_id;
        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        $usuario->roles()->sync($request->roles);
        $usuario->load('roles');

        return response()->json([
            'message' => 'Roles asignados exitosamente',
            'data' => new UsuarioResource($usuario)
        ], 200);
    }

    /**
     * Cambiar contraseña de un usuario
     * 
     * POST /api/usuarios/{id}/cambiar-password
     */
    public function cambiarPassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'password_actual' => ['required', 'string'],
            'password_nueva' => ['required', 'string', 'min:8', 'confirmed']
        ]);

        $empresaId = auth()->user()->empresa_id;
        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        // Verificar password actual
        if (!Hash::check($request->password_actual, $usuario->password_hash)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta'
            ], 422);
        }

        $usuario->password_hash = Hash::make($request->password_nueva);
        $usuario->save();

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente'
        ], 200);
    }
}
