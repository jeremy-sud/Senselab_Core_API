<?php

/**
 * Controlador para asignaciones `RolUsuario`.
 *
 * Proporciona endpoints para asignar roles a usuarios, listarlos y remover
 * asignaciones. Usa `OpenApi` attributes para documentación automática.
 */
namespace App\Http\Controllers;

use App\Models\RolUsuario;
use App\Models\Usuario;
use App\Http\Requests\StoreRolUsuarioRequest;
use App\Http\Requests\UpdateRolUsuarioRequest;
use App\Http\Resources\RolUsuarioResource;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AsignarRolesUsuarioRequest;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;


#[OA\Tag(
    name: 'Rol-Usuario',
    description: 'Gestión de roles asignados a usuarios'
)]
class RolUsuarioController extends Controller
{
    use HasEmpresaContext;
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

        $empresaId = $this->getEmpresaId();

        $query = RolUsuario::where('activo', 1)->where('eliminado', 0)
            ->whereHas('usuario', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

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
        $rolUsuario->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Asignación de rol eliminada exitosamente'
        ]);
    }

    public function rolesPorUsuario(int $usuarioId): JsonResponse
    {
        $this->authorize('viewAny', RolUsuario::class);

        $empresaId = $this->getEmpresaId();

        // Validar que el usuario pertenece a la empresa actual
        Usuario::where('empresa_id', $empresaId)->findOrFail($usuarioId);

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
        $this->authorize('viewAny', RolUsuario::class);

        $empresaId = $this->getEmpresaId();

        $usuarios = RolUsuario::where('rol_id', $rolId)
            ->where('activo', 1)
            ->where('eliminado', 0)
            ->whereHas('usuario', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => RolUsuarioResource::collection($usuarios)
        ]);
    }

    public function asignarRoles(AsignarRolesUsuarioRequest $request): JsonResponse
    {
        $this->authorize('create', RolUsuario::class);

        $empresaId = $this->getEmpresaId();

        // Validar que el usuario pertenece a la empresa actual
        Usuario::where('empresa_id', $empresaId)->findOrFail($request->usuario_id);

        $rolesAsignados = [];

        DB::transaction(function () use ($request, &$rolesAsignados) {
            // Desactivar roles actuales
            RolUsuario::where('usuario_id', $request->usuario_id)
                ->update(['activo' => 0]);

            // Asignar nuevos roles
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
        });

        return response()->json([
            'success' => true,
            'message' => 'Roles asignados exitosamente',
            'data' => RolUsuarioResource::collection($rolesAsignados)
        ]);
    }
}
