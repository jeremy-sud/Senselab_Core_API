<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoRequest;
use App\Http\Resources\EmpleadoResource;
use App\Models\Empleado;
use App\Services\EmpleadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * EmpleadoController - Versión Refactorizada (FASE 8)
 *
 * Controlador simplificado usando Service Layer Pattern.
 * Delegación: Validación → Service → Response
 *
 * Reducción de líneas: 330 → ~160 (-52%)
 * Refactorización completada: FASE 8
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class EmpleadoController extends Controller
{
    public function __construct(private EmpleadoService $empleadoService) {}

    /**
     * GET /api/empleados
     * Listar empleados con filtros opcionales
     */
    #[OA\Get(
        path: '/api/empleados',
        summary: 'Listar empleados',
        description: 'Lista empleados de la empresa autenticada con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Empleados'],
        parameters: [
            new OA\Parameter(name: 'activo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'cargo_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'departamento_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Empleado'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Empleado::class);

        $empleados = $this->empleadoService->listar(
            filtros: $request->only(['departamento_id', 'cargo_id', 'search']),
            perPage: (int) $request->input('per_page', 15)
        );

        return EmpleadoResource::collection($empleados);
    }

    /**
     * POST /api/empleados
     * Crear un nuevo empleado
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
                required: ['nombre', 'primer_apellido', 'tipo_documento', 'numero_documento', 'fecha_nacimiento', 'fecha_ingreso', 'departamento_id', 'cargo_id', 'salario'],
                properties: [
                    new OA\Property(property: 'usuario_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Carlos'),
                    new OA\Property(property: 'primer_apellido', type: 'string', example: 'Rodríguez'),
                    new OA\Property(property: 'segundo_apellido', type: 'string', nullable: true),
                    new OA\Property(property: 'tipo_documento', type: 'string', enum: ['cedula', 'pasaporte', 'residencia']),
                    new OA\Property(property: 'numero_documento', type: 'string', example: '1-2345-6789'),
                    new OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date'),
                    new OA\Property(property: 'fecha_ingreso', type: 'string', format: 'date'),
                    new OA\Property(property: 'departamento_id', type: 'integer'),
                    new OA\Property(property: 'cargo_id', type: 'integer'),
                    new OA\Property(property: 'salario', type: 'number'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Empleado creado')]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Empleado::class);

        $validated = $request->validate([
            'usuario_id' => 'nullable|exists:usuarios,id|unique:empleados,usuario_id',
            'nombre' => 'required|string|max:255',
            'primer_apellido' => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'tipo_documento' => 'required|string|max:50|in:cedula,pasaporte,residencia',
            'numero_documento' => 'required|string|max:100|unique:empleados,numero_documento',
            'fecha_nacimiento' => 'required|date',
            'fecha_ingreso' => 'required|date',
            'departamento_id' => 'required|exists:departamentos,id',
            'cargo_id' => 'required|exists:cargos,id',
            'salario' => 'required|numeric|min:0',
            'email' => 'nullable|email|unique:empleados,email',
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
        ]);

        try {
            $empleado = $this->empleadoService->crear($validated);

            return (new EmpleadoResource($empleado))
                ->additional(['message' => 'Empleado creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear empleado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/empleados/{id}
     * Obtener un empleado específico
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
        $empleado = $this->empleadoService->obtener((int) $id);
        $this->authorize('view', $empleado);

        return new EmpleadoResource($empleado);
    }

    /**
     * PUT /api/empleados/{id}
     * Actualizar un empleado existente
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
                    new OA\Property(property: 'primer_apellido', type: 'string'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'salario', type: 'number', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean')
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
            'usuario_id' => 'nullable|exists:usuarios,id|unique:empleados,usuario_id,' . $id,
            'nombre' => 'sometimes|string|max:255',
            'primer_apellido' => 'sometimes|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'tipo_documento' => 'sometimes|string|max:50|in:cedula,pasaporte,residencia',
            'numero_documento' => 'sometimes|string|max:100|unique:empleados,numero_documento,' . $id,
            'fecha_nacimiento' => 'sometimes|date',
            'fecha_ingreso' => 'sometimes|date',
            'departamento_id' => 'sometimes|exists:departamentos,id',
            'cargo_id' => 'sometimes|exists:cargos,id',
            'salario' => 'sometimes|numeric|min:0',
            'email' => 'nullable|email|unique:empleados,email,' . $id,
            'telefono' => 'nullable|string|max:50',
            'direccion' => 'nullable|string',
        ]);

        $empleado = $this->empleadoService->actualizar($empleado, $validated);

        return (new EmpleadoResource($empleado))
            ->additional(['message' => 'Empleado actualizado exitosamente']);
    }

    /**
     * DELETE /api/empleados/{id}
     * Eliminar un empleado (soft delete)
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

        $this->empleadoService->eliminar($empleado);

        return response()->json(['message' => 'Empleado eliminado exitosamente']);
    }
}
