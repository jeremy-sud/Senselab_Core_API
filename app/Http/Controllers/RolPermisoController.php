<?php

namespace App\Http\Controllers;

use App\Models\RolPermiso;
use App\Http\Requests\StoreRolPermisoRequest;
use App\Http\Requests\UpdateRolPermisoRequest;
use App\Http\Resources\RolPermisoResource;
use App\Services\RolPermisoService;
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
    public function __construct(
        private readonly RolPermisoService $service,
    ) {}

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
        $rolesPermisos = $this->service->listar(
            $request->only(['rol_id', 'permiso_id', 'activo']),
            (int) $request->get('per_page', 15)
        );

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
        try {
            $rolPermiso = $this->service->asignar($request->validated());
        } catch (\App\Exceptions\BusinessException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permiso asignado al rol exitosamente',
            'data' => new RolPermisoResource($rolPermiso)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/rol-permiso/{id}',
        summary: 'Obtener relación rol-permiso',
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
    #[OA\Put(
        path: '/api/rol-permiso/{id}',
        summary: 'Actualizar relación rol-permiso',
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
    public function update(UpdateRolPermisoRequest $request, RolPermiso $rolPermiso): JsonResponse
    {
        $this->service->actualizar($rolPermiso, $request->validated());

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
        try {
            $this->service->remover($rol, $permiso);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'La relación rol-permiso no existe'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permiso removido del rol exitosamente'
        ]);
    }

    /**
     * Asignar múltiples permisos a un rol.
     */
    #[OA\Post(
        path: '/api/rol-permiso/asignar-permisos',
        summary: 'Asignar múltiples permisos a un rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        responses: [
            new OA\Response(response: 201, description: 'Permisos asignados'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function asignarPermisos(AsignarPermisosRequest $request): JsonResponse
    {
        $resultado = $this->service->asignarMultiples($request->rol_id, $request->permiso_ids);

        return response()->json([
            'success' => true,
            'message' => count($resultado['asignados']) . ' permiso(s) asignado(s) exitosamente',
            'data' => [
                'asignados' => RolPermisoResource::collection($resultado['asignados']),
                'ya_existentes' => $resultado['ya_existentes'],
            ]
        ], 201);
    }

    /**
     * Remover múltiples permisos de un rol.
     */
    #[OA\Post(
        path: '/api/rol-permiso/remover-permisos',
        summary: 'Remover múltiples permisos de un rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        responses: [
            new OA\Response(response: 200, description: 'Permisos removidos'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function removerPermisos(RemoverPermisosRequest $request): JsonResponse
    {
        $removidos = $this->service->removerMultiples($request->rol_id, $request->permiso_ids);

        return response()->json([
            'success' => true,
            'message' => "{$removidos} permiso(s) removido(s) exitosamente"
        ]);
    }

    /**
     * Obtener todos los permisos de un rol.
     */
    #[OA\Get(
        path: '/api/rol-permiso/permisos-por-rol/{rolId}',
        summary: 'Obtener todos los permisos de un rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        parameters: [
            new OA\Parameter(name: 'rolId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]
    public function permisosPorRol(int $rolId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($this->service->permisosPorRol($rolId))
        ]);
    }

    /**
     * Obtener todos los roles que tienen un permiso específico.
     */
    #[OA\Get(
        path: '/api/rol-permiso/roles-por-permiso/{permisoId}',
        summary: 'Obtener todos los roles que tienen un permiso',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        parameters: [
            new OA\Parameter(name: 'permisoId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]
    public function rolesPorPermiso(int $permisoId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => RolPermisoResource::collection($this->service->rolesPorPermiso($permisoId))
        ]);
    }

    /**
     * Sincronizar permisos de un rol (reemplaza todos los existentes).
     */
    #[OA\Post(
        path: '/api/rol-permiso/sincronizar-permisos',
        summary: 'Sincronizar permisos de un rol',
        security: [['sanctum' => []]],
        tags: ['Rol-Permiso'],
        responses: [
            new OA\Response(response: 200, description: 'Permisos sincronizados'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function sincronizarPermisos(SincronizarPermisosRequest $request): JsonResponse
    {
        $nuevos = $this->service->sincronizar($request->rol_id, $request->permiso_ids);

        return response()->json([
            'success' => true,
            'message' => 'Permisos sincronizados exitosamente',
            'data' => RolPermisoResource::collection($nuevos)
        ]);
    }
}
