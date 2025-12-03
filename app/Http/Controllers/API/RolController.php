<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRolRequest;
use App\Http\Requests\UpdateRolRequest;
use App\Http\Requests\AsignarPermisosRolRequest;
use App\Http\Resources\RolResource;
use App\Models\Rol;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

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
    use HasCacheableQueries;

    protected array $cacheTags = ['roles', 'rbac'];
    protected int $cacheTTL = 1800; // 30 minutos - datos RBAC cambian ocasionalmente

    /**
     * Listar todos los roles activos
     *
     * GET /api/roles
     */
    #[OA\Get(
        path: '/api/roles',
        summary: 'Listar roles',
        description: 'Obtiene todos los roles del sistema con sus permisos',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [
            new OA\Parameter(name: 'activo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Rol'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Rol::class);

        $cacheKey = $this->getCacheKey('index', [
            'activo' => $request->input('activo')
        ]);

        $roles = $this->cacheQueryIfEnabled($cacheKey, function() use ($request) {
            $query = Rol::query()->with(['permisos']);

            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            return $query->get();
        });

        return RolResource::collection($roles);
    }

    /**
     * Crear un nuevo rol
     *
     * POST /api/roles
     */
    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear rol',
        description: 'Crea nuevo rol y opcionalmente asigna permisos',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Gerente de Ventas'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Acceso completo al módulo de ventas'),
                    new OA\Property(property: 'activo', type: 'boolean', example: true),
                    new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), nullable: true, example: [1, 2, 3])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rol creado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Rol')]))
        ]
    )]
    public function store(StoreRolRequest $request): JsonResponse
    {
        $this->authorize('create', Rol::class);

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

        $this->flushCache();

        return (new RolResource($rol))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un rol específico
     *
     * GET /api/roles/{id}
     */
    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Obtener rol',
        description: 'Detalles del rol con permisos y usuarios asignados',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Rol encontrado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(int $id): RolResource
    {
        $rol = Rol::with(['permisos', 'usuarios'])->findOrFail($id);

        $this->authorize('view', $rol);

        return new RolResource($rol);
    }

    /**
     * Actualizar un rol existente
     *
     * PUT/PATCH /api/roles/{id}
     */
    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Actualizar rol',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
    public function update(UpdateRolRequest $request, int $id): RolResource
    {
        $rol = Rol::findOrFail($id);

        $this->authorize('update', $rol);

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

        $this->flushCache();

        return new RolResource($rol);
    }

    /**
     * Eliminar un rol (soft delete)
     *
     * DELETE /api/roles/{id}
     */
    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Eliminar rol',
        description: 'Soft delete. Valida que no tenga usuarios asignados',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado'),
            new OA\Response(response: 422, description: 'No se puede eliminar con usuarios asignados')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $rol = Rol::findOrFail($id);

        $this->authorize('delete', $rol);

        // Validar que no tenga usuarios asignados
        if ($rol->usuarios()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque tiene usuarios asignados'
            ], 422);
        }

        $rol->eliminado = 1;
        $rol->activo = 0;
        $rol->save();

        $this->flushCache();

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
    #[OA\Post(
        path: '/api/roles/{id}/permisos',
        summary: 'Asignar permisos a rol',
        description: 'Sincroniza permisos del rol (reemplaza existentes)',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permisos'],
                properties: [
                    new OA\Property(property: 'permisos', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3, 4, 5])
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Permisos asignados')]
    )]
    public function asignarPermisos(AsignarPermisosRolRequest $request, int $id): JsonResponse
    {

        $rol = Rol::findOrFail($id);
        $rol->permisos()->sync($request->permisos);
        $rol->load('permisos');

        $this->flushCache();

        return response()->json([
            'message' => 'Permisos asignados exitosamente',
            'data' => new RolResource($rol)
        ], 200);
    }

    /**
     * Remover un permiso específico de un rol
     */
    public function removerPermiso(int $id, int $permiso_id): JsonResponse
    {
        $rol = Rol::findOrFail($id);
        $rol->permisos()->detach($permiso_id);
        $rol->load('permisos');

        $this->flushCache();

        return response()->json([
            'message' => 'Permiso removido exitosamente',
            'data' => new RolResource($rol)
        ], 200);
    }
}
