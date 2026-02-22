<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoRequest;
use App\Http\Resources\EmpleadoResource;
use App\Models\Empleado;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para gestión de empleados
 *
 * Maneja el CRUD completo de empleados con:
 * - Filtrado por empresa (multi-tenant)
 * - Validación de documentos únicos
 * - Relaciones con cargos y usuarios
 * - Soft deletes
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class EmpleadoController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['empleados', 'rrhh'];
    protected int $cacheTTL = 900; // 15 minutos - datos de RRHH moderadamente dinámicos

    /**
     * Listar todos los empleados de la empresa del usuario autenticado
     *
     * GET /api/empleados
     * Query params opcionales:
     * - activo: boolean (filtrar por estado)
     * - cargo_id: int (filtrar por cargo)
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/empleados',
        summary: 'Listar empleados',
        description: 'Lista empleados de la empresa autenticada con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        parameters: [
            new OA\Parameter(
                name: 'activo',
                description: 'Filtrar por estado activo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'cargo_id',
                description: 'Filtrar por cargo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Empleado'))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Empleado::class);

        $cacheKey = $this->generateCacheKey('empleados.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);

            $query = Empleado::with(['usuario', 'departamento', 'cargo'])
                ->activos();

            if ($request->filled('departamento_id')) {
                $query->where('departamento_id', $request->departamento_id);
            }

            if ($request->filled('cargo_id')) {
                $query->where('cargo_id', $request->cargo_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('primer_nombre', 'like', "%{$search}%")
                      ->orWhere('primer_apellido', 'like', "%{$search}%")
                      ->orWhere('numero_identificacion', 'like', "%{$search}%");
                });
            }

            $empleados = $query->orderBy('primer_apellido')
                ->orderBy('primer_nombre')
                ->paginate($perPage);

            return EmpleadoResource::collection($empleados);
        });
    }

    /**
     * Crear un nuevo empleado
     *
     * POST /api/empleados
     *
     * @param StoreEmpleadoRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/empleados',
        summary: 'Crear empleado',
        description: 'Registra un nuevo empleado en la empresa autenticada',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'apellidos', 'numero_identificacion'],
                properties: [
                    new OA\Property(property: 'cargo_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'numero_identificacion', type: 'string', example: '1-2345-6789'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Carlos'),
                    new OA\Property(property: 'apellidos', type: 'string', example: 'Rodríguez'),
                    new OA\Property(property: 'email', type: 'string', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'fecha_ingreso', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'salario_base', type: 'number', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Empleado creado')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Empleado::class);

        $validated = $request->validate([
            'usuario_id' => 'nullable|exists:users,id|unique:empleados,usuario_id',
            'primer_nombre' => 'required|string|max:50',
            'segundo_nombre' => 'nullable|string|max:50',
            'primer_apellido' => 'required|string|max:50',
            'segundo_apellido' => 'nullable|string|max:50',
            'tipo_identificacion' => 'required|string|in:cedula,pasaporte,residencia',
            'numero_identificacion' => 'required|string|max:20|unique:empleados,numero_identificacion',
            'fecha_nacimiento' => 'required|date',
            'fecha_ingreso' => 'required|date',
            'departamento_id' => 'required|exists:departamentos,id',
            'cargo_id' => 'required|exists:cargos,id',
            'salario_base' => 'required|numeric|min:0',
            'email_corporativo' => 'nullable|email|unique:empleados,email_corporativo',
            'telefono_movil' => 'nullable|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $empleado = Empleado::create($validated);

            DB::commit();
            $this->clearCache();

            return (new EmpleadoResource($empleado->load(['usuario', 'departamento', 'cargo'])))
                ->additional(['message' => 'Empleado creado exitosamente'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un empleado específico
     *
     * GET /api/empleados/{id}
     *
     * @param string $id
     * @return EmpleadoResource
     */
    #[OA\Get(
        path: '/api/empleados/{id}',
        summary: 'Obtener empleado',
        description: 'Detalles de un empleado',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Empleado encontrado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Empleado')])),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(string $id): EmpleadoResource
    {
        $empleado = Empleado::with(['usuario', 'departamento', 'cargo'])->findOrFail($id);
        $this->authorize('view', $empleado);

        $cacheKey = $this->generateCacheKey("empleados.show.{$id}");

        return $this->getCached($cacheKey, function () use ($empleado) {
            return new EmpleadoResource($empleado);
        });
    }

    /**
     * Actualizar un empleado existente
     *
     * PUT/PATCH /api/empleados/{id}
     *
     * @param UpdateEmpleadoRequest $request
     * @param string $id
     * @return EmpleadoResource
     */
    #[OA\Put(
        path: '/api/empleados/{id}',
        summary: 'Actualizar empleado',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'apellidos', type: 'string'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'salario_base', type: 'number', nullable: true)
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
    public function update(Request $request, string $id): EmpleadoResource
    {
        $empleado = Empleado::findOrFail($id);
        $this->authorize('update', $empleado);

        $validated = $request->validate([
            'usuario_id' => 'nullable|exists:users,id|unique:empleados,usuario_id,' . $id,
            'primer_nombre' => 'sometimes|string|max:50',
            'segundo_nombre' => 'nullable|string|max:50',
            'primer_apellido' => 'sometimes|string|max:50',
            'segundo_apellido' => 'nullable|string|max:50',
            'tipo_identificacion' => 'sometimes|string|in:cedula,pasaporte,residencia',
            'numero_identificacion' => 'sometimes|string|max:20|unique:empleados,numero_identificacion,' . $id,
            'fecha_nacimiento' => 'sometimes|date',
            'fecha_ingreso' => 'sometimes|date',
            'departamento_id' => 'sometimes|exists:departamentos,id',
            'cargo_id' => 'sometimes|exists:cargos,id',
            'salario_base' => 'sometimes|numeric|min:0',
            'email_corporativo' => 'nullable|email|unique:empleados,email_corporativo,' . $id,
            'telefono_movil' => 'nullable|string|max:20',
            'estado' => 'sometimes|in:activo,inactivo,suspendido,vacaciones',
        ]);

        DB::beginTransaction();
        try {
            $empleado->update($validated);

            DB::commit();
            $this->clearCache();

            return (new EmpleadoResource($empleado->fresh(['usuario', 'departamento', 'cargo'])))
                ->additional(['message' => 'Empleado actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar un empleado (soft delete)
     *
     * DELETE /api/empleados/{id}
     *
     * @param string $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/empleados/{id}',
        summary: 'Eliminar empleado',
        description: 'Soft delete del empleado',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Eliminado')]
    )]
    public function destroy(string $id): JsonResponse
    {
        $empleado = Empleado::findOrFail($id);
        $this->authorize('delete', $empleado);

        try {
            $empleado->delete(); // Soft delete estándar de Laravel
            $this->clearCache();

            return response()->json([
                'message' => 'Empleado eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
