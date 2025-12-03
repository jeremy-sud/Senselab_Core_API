<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Http\Requests\AsignarRolesRequest;
use App\Http\Requests\CambiarPasswordRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

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
    use HasCacheableQueries;

    protected $cacheTags = ['usuarios', 'auth'];
    protected $cacheTTL = 900; // 15 minutos
    /**
     * Listar todos los usuarios de la empresa
     *
     * GET /api/usuarios
     */
    #[OA\Get(
        path: '/api/usuarios',
        summary: 'Listar usuarios',
        description: 'Obtiene todos los usuarios de la empresa autenticada',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [
            new OA\Parameter(name: 'activo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'cargo_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Usuario'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Usuario::class);

        $empresaId = auth('sanctum')->user()->empresa_id;

        $usuarios = $this->cacheQueryIfEnabled(
            $this->getCacheKey('index', array_merge($request->all(), ['empresa_id' => $empresaId])),
            function() use ($request, $empresaId) {
                $query = Usuario::where('empresa_id', $empresaId)
                    ->with(['roles', 'cargo', 'empresa']);

                if ($request->has('activo')) {
                    $query->where('activo', $request->boolean('activo'));
                }

                if ($request->filled('cargo_id')) {
                    $query->where('cargo_id', $request->cargo_id);
                }

                return $query->get();
            }
        );

        return UsuarioResource::collection($usuarios);
    }

    /**
     * Crear un nuevo usuario
     *
     * POST /api/usuarios
     */
    #[OA\Post(
        path: '/api/usuarios',
        summary: 'Crear usuario',
        description: 'Crea nuevo usuario con password hasheado. Opcionalmente asigna roles',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'nombre', 'apellido1'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@ejemplo.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password123!'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellido1', type: 'string', example: 'Pérez'),
                    new OA\Property(property: 'apellido2', type: 'string', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'cargo_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer'), nullable: true, example: [1, 2])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Usuario')]))
        ]
    )]
    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        $this->authorize('create', Usuario::class);

        $validated = $request->validated();
        $validated['empresa_id'] = auth('sanctum')->user()->empresa_id;
        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']);

        $usuario = Usuario::create($validated);

        // Asignar roles si fueron proporcionados
        if (isset($validated['roles'])) {
            $usuario->roles()->attach($validated['roles']);
        }

        $usuario->load(['roles', 'cargo', 'empresa']);

        $this->flushCache();

        return (new UsuarioResource($usuario))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un usuario específico
     *
     * GET /api/usuarios/{id}
     */
    #[OA\Get(
        path: '/api/usuarios/{id}',
        summary: 'Obtener usuario',
        description: 'Detalles completos con roles, cargo y empresa',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Usuario encontrado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(int $id): UsuarioResource
    {
        $empresaId = auth('sanctum')->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)
            ->with(['roles.permisos', 'cargo', 'empresa', 'empleado'])
            ->findOrFail($id);

        $this->authorize('view', $usuario);

        return new UsuarioResource($usuario);
    }

    /**
     * Actualizar un usuario existente
     *
     * PUT/PATCH /api/usuarios/{id}
     */
    #[OA\Put(
        path: '/api/usuarios/{id}',
        summary: 'Actualizar usuario',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
    public function update(UpdateUsuarioRequest $request, int $id): UsuarioResource
    {
        $empresaId = auth('sanctum')->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('update', $usuario);

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

        $this->flushCache();

        return new UsuarioResource($usuario);
    }

    /**
     * Eliminar un usuario (soft delete)
     *
     * DELETE /api/usuarios/{id}
     */
    #[OA\Delete(
        path: '/api/usuarios/{id}',
        summary: 'Eliminar usuario',
        description: 'Soft delete. Valida que no sea el usuario autenticado. Revoca todos sus tokens',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado'),
            new OA\Response(response: 422, description: 'No puede eliminarse a sí mismo')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth('sanctum')->user()->empresa_id;

        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        $this->authorize('delete', $usuario);

        // No permitir que el usuario se elimine a sí mismo
        if ($usuario->id === auth('sanctum')->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de usuario'
            ], 422);
        }

        $usuario->eliminado = 1;
        $usuario->activo = 0;
        $usuario->save();

        // Revocar todos los tokens de acceso
        $usuario->tokens()->delete();

        $this->flushCache();

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
    #[OA\Post(
        path: '/api/usuarios/{id}/roles',
        summary: 'Asignar roles a usuario',
        description: 'Sincroniza los roles del usuario (reemplaza roles existentes)',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['roles'],
                properties: [
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3])
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Roles asignados')]
    )]
    public function asignarRoles(AsignarRolesRequest $request, int $id): JsonResponse
    {

        $empresaId = auth('sanctum')->user()->empresa_id;
        $usuario = Usuario::where('empresa_id', $empresaId)->findOrFail($id);

        $usuario->roles()->sync($request->roles);
        $usuario->load('roles');

        $this->flushCache();

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
    #[OA\Post(
        path: '/api/usuarios/{id}/cambiar-password',
        summary: 'Cambiar contraseña',
        description: 'Valida password actual antes de cambiar',
        security: [['sanctum' => []]],
        tags: ['Usuarios'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password_actual', 'password_nueva', 'password_nueva_confirmation'],
                properties: [
                    new OA\Property(property: 'password_actual', type: 'string', format: 'password', example: 'OldPassword123!'),
                    new OA\Property(property: 'password_nueva', type: 'string', format: 'password', example: 'NewPassword456!'),
                    new OA\Property(property: 'password_nueva_confirmation', type: 'string', format: 'password', example: 'NewPassword456!')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Contraseña cambiada'),
            new OA\Response(response: 422, description: 'Password actual incorrecta')
        ]
    )]
    public function cambiarPassword(CambiarPasswordRequest $request, int $id): JsonResponse
    {
        $empresaId = auth('sanctum')->user()->empresa_id;
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
