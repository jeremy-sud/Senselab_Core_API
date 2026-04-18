<?php

/**
 * Controlador para asignaciones `RolUsuario`.
 *
 * Proporciona endpoints para asignar roles a usuarios, listarlos y remover
 * asignaciones. Usa `OpenApi` attributes para documentación automática.
 */
namespace App\Http\Controllers;

use App\Models\RolUsuario;
use App\Http\Requests\StoreRolUsuarioRequest;
use App\Http\Requests\UpdateRolUsuarioRequest;
use App\Http\Resources\RolUsuarioResource;
use App\Services\RolUsuarioService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AsignarRolesUsuarioRequest;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Rol-Usuario',
    description: 'Gestión de roles asignados a usuarios'
)]
class RolUsuarioController extends Controller
{
    use HasEmpresaContext;

    public function __construct(
        private readonly RolUsuarioService $service,
    ) {}

        #[OA\Get(
        path: '/api/rol-usuario',
        summary: 'Listar roles de usuarios',
        security: [['sanctum' => []]],
        tags: ['Rol-Usuario'],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
        ]
    )]

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RolUsuario::class);

        $rolesUsuarios = $this->service->listar(
            $this->getEmpresaId(),
            $request->only(['usuario_id', 'rol_id']),
            (int) $request->get('per_page', 15)
        );

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

        #[OA\Post(
        path: '/api/rol-usuario',
        summary: 'Asignar rol a usuario',
        security: [['sanctum' => []]],
        tags: ['Rol-Usuario'],
        responses: [
            new OA\Response(response: 201, description: 'Recurso creado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]


    public function store(StoreRolUsuarioRequest $request): JsonResponse
    {
        $rolUsuario = $this->service->asignar($request->validated());

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
        $this->service->actualizar($rolUsuario, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Asignación de rol actualizada exitosamente',
            'data' => new RolUsuarioResource($rolUsuario)
        ]);
    }

        #[OA\Delete(
        path: '/api/rol-usuario/{id}',
        summary: 'Quitar rol de usuario',
        security: [['sanctum' => []]],
        tags: ['Rol-Usuario'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Operación exitosa'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]


    public function destroy(RolUsuario $rolUsuario): JsonResponse
    {
        $this->service->eliminar($rolUsuario);

        return response()->json([
            'success' => true,
            'message' => 'Asignación de rol eliminada exitosamente'
        ]);
    }

    public function rolesPorUsuario(int $usuarioId): JsonResponse
    {
        $this->authorize('viewAny', RolUsuario::class);

        $roles = $this->service->rolesPorUsuario($this->getEmpresaId(), $usuarioId);

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($roles)
        ]);
    }

    public function usuariosPorRol(int $rolId): JsonResponse
    {
        $this->authorize('viewAny', RolUsuario::class);

        $usuarios = $this->service->usuariosPorRol($this->getEmpresaId(), $rolId);

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($usuarios)
        ]);
    }

    public function asignarRoles(AsignarRolesUsuarioRequest $request): JsonResponse
    {
        $this->authorize('create', RolUsuario::class);

        $rolesAsignados = $this->service->asignarRoles(
            $this->getEmpresaId(),
            $request->usuario_id,
            $request->roles
        );

        return response()->json([
            'success' => true,
            'message' => 'Roles asignados exitosamente',
            'data' => RolUsuarioResource::collection($rolesAsignados)
        ]);
    }
}
