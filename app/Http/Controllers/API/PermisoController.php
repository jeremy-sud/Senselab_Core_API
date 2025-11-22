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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: '/api/permisos',
        summary: 'Listar permisos',
        description: 'Obtiene todos los permisos del sistema con filtros',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [
            new OA\Parameter(name: 'activo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'modulo', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'ventas'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Permiso'))]))
        ]
    )]
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
     * Obtener permisos agrupados por módulo
     * 
     * GET /api/permisos/grouped
     */
    public function grouped(): JsonResponse
    {
        $permisos = Permiso::where('activo', true)
            ->where('eliminado', false)
            ->get()
            ->groupBy('modulo')
            ->map(function ($group) {
                return $group->map(function ($permiso) {
                    return [
                        'id' => $permiso->id,
                        'nombre' => $permiso->nombre,
                        'slug' => $permiso->slug,
                        'descripcion' => $permiso->descripcion,
                    ];
                });
            });

        return response()->json([
            'data' => $permisos
        ]);
    }

    /**
     * Crear un nuevo permiso
     * 
     * POST /api/permisos
     */
    #[OA\Post(
        path: '/api/permisos',
        summary: 'Crear permiso',
        description: 'Crea nuevo permiso en el sistema',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'accion'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Ver Ventas'),
                    new OA\Property(property: 'accion', type: 'string', example: 'ventas.view'),
                    new OA\Property(property: 'modulo', type: 'string', nullable: true, example: 'ventas'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Permiso creado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Permiso')]))
        ]
    )]
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
    #[OA\Get(
        path: '/api/permisos/{id}',
        summary: 'Obtener permiso',
        description: 'Detalles del permiso con roles asignados',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Permiso encontrado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
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
    #[OA\Put(
        path: '/api/permisos/{id}',
        summary: 'Actualizar permiso',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
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
    #[OA\Delete(
        path: '/api/permisos/{id}',
        summary: 'Eliminar permiso',
        description: 'Soft delete. Valida que no esté asignado a roles',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado'),
            new OA\Response(response: 422, description: 'No se puede eliminar con roles asignados')
        ]
    )]
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
    #[OA\Get(
        path: '/api/permisos/modulos',
        summary: 'Listar módulos',
        description: 'Obtiene lista única de módulos del sistema',
        security: [['sanctum' => []]],
        tags: ['Roles y Permisos'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de módulos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'string'), example: ['ventas', 'compras', 'inventario', 'contabilidad'])
                    ]
                )
            )
        ]
    )]
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
