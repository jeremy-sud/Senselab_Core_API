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

        $empresaId = auth()->user()->empresa_id;
        
        $cacheKey = $this->getCacheKey('index', [
            'empresa_id' => $empresaId,
            'activo' => $request->input('activo'),
            'cargo_id' => $request->input('cargo_id')
        ]);

        $empleados = $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = Empleado::where('empresa_id', $empresaId)
                ->with(['cargo']);

            // Filtro opcional por estado activo
            if ($request->has('activo')) {
                $query->where('activo', $request->boolean('activo'));
            }

            // Filtro opcional por cargo
            if ($request->filled('cargo_id')) {
                $query->where('cargo_id', $request->cargo_id);
            }

            return $query->get();
        });

        return EmpleadoResource::collection($empleados);
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
    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $this->authorize('create', Empleado::class);

        $validated = $request->validated();
        $validated['empresa_id'] = auth()->user()->empresa_id;

        $empleado = Empleado::create($validated);
        $empleado->load(['cargo']);

        $this->flushCache();

        return (new EmpleadoResource($empleado))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Mostrar un empleado específico
     * 
     * GET /api/empleados/{id}
     * 
     * @param int $id
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
    public function show(int $id): EmpleadoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)
            ->with(['cargo'])
            ->findOrFail($id);
        $this->authorize('view', $empleado);

        return new EmpleadoResource($empleado);
    }

    /**
     * Actualizar un empleado existente
     * 
     * PUT/PATCH /api/empleados/{id}
     * 
     * @param UpdateEmpleadoRequest $request
     * @param int $id
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
    public function update(UpdateEmpleadoRequest $request, int $id): EmpleadoResource
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)->findOrFail($id);
        $this->authorize('update', $empleado);

        $empleado->update($request->validated());
        $empleado->load(['cargo']);

        $this->flushCache();

        return new EmpleadoResource($empleado);
    }

    /**
     * Eliminar un empleado (soft delete)
     * 
     * DELETE /api/empleados/{id}
     * 
     * @param int $id
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
    public function destroy(int $id): JsonResponse
    {
        $empresaId = auth()->user()->empresa_id;

        $empleado = Empleado::where('empresa_id', $empresaId)->findOrFail($id);
        $this->authorize('delete', $empleado);

        // Soft delete personalizado
        $empleado->eliminado = 1;
        $empleado->activo = 0;
        $empleado->save();

        $this->flushCache();

        return response()->json([
            'message' => 'Empleado eliminado exitosamente',
            'data' => new EmpleadoResource($empleado)
        ], 200);
    }
}
