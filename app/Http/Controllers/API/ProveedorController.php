<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Services\ProveedorService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Http\Resources\ProveedorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * ProveedorController - Versión Refactorizada (FASE 8)
 *
 * Controlador simplificado usando Service Layer Pattern.
 * Delegación: Validación (FormRequest) → Service → Response
 *
 * Reducción de líneas: 503 → ~160 (-68%)
 * Refactorización completada: FASE 8
 */
class ProveedorController extends Controller
{
    public function __construct(private ProveedorService $proveedorService) {}

    /**
     * GET /api/proveedores
     * Listar proveedores con filtros opcionales
     */
    #[OA\Get(
        path: '/api/proveedores',
        summary: 'Listar proveedores',
        description: 'Obtiene un listado paginado de proveedores con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'Distribuidora')),
            new OA\Parameter(name: 'empresa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'activos', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', example: true))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado de proveedores obtenido exitosamente')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Proveedor::class);

        $proveedores = $this->proveedorService->listar(
            filtros: $request->only(['search', 'empresa_id', 'activos']),
            perPage: (int) $request->input('per_page', 15)
        );

        return ProveedorResource::collection($proveedores);
    }

    /**
     * POST /api/proveedores
     * Crear un nuevo proveedor
     */
    #[OA\Post(
        path: '/api/proveedores',
        summary: 'Crear un nuevo proveedor',
        description: 'Registra un nuevo proveedor en el sistema',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05'], example: '02'),
                    new OA\Property(property: 'numero_identificacion', type: 'string', example: '3-101-123456'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Distribuidora Nacional S.A.'),
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true),
                    new OA\Property(property: 'canton', type: 'string', nullable: true),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Proveedor creado exitosamente'),
            new OA\Response(response: 422, description: 'Errores de validación')
        ]
    )]
    public function store(StoreProveedorRequest $request): JsonResponse
    {
        $this->authorize('create', Proveedor::class);

        $proveedor = $this->proveedorService->crear($request->validated());

        return (new ProveedorResource($proveedor))
            ->additional(['message' => 'Proveedor creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/proveedores/{id}
     * Obtener un proveedor específico
     */
    #[OA\Get(
        path: '/api/proveedores/{id}',
        summary: 'Obtener un proveedor específico',
        description: 'Obtiene los detalles de un proveedor incluyendo órdenes recientes y cuentas por pagar pendientes',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Proveedor obtenido exitosamente'),
            new OA\Response(response: 404, description: 'Proveedor no encontrado')
        ]
    )]
    public function show(int $id): ProveedorResource
    {
        $proveedor = $this->proveedorService->obtener($id);
        $this->authorize('view', $proveedor);

        return new ProveedorResource($proveedor);
    }

    /**
     * PUT /api/proveedores/{id}
     * Actualizar un proveedor existente
     */
    #[OA\Put(
        path: '/api/proveedores/{id}',
        summary: 'Actualizar un proveedor',
        description: 'Actualiza la información de un proveedor existente',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo_identificacion', type: 'string'),
                    new OA\Property(property: 'numero_identificacion', type: 'string'),
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Proveedor actualizado exitosamente'),
            new OA\Response(response: 404, description: 'Proveedor no encontrado'),
            new OA\Response(response: 422, description: 'Errores de validación')
        ]
    )]
    public function update(UpdateProveedorRequest $request, int $id): ProveedorResource
    {
        $proveedor = Proveedor::findOrFail($id);
        $this->authorize('update', $proveedor);

        $proveedor = $this->proveedorService->actualizar($proveedor, $request->validated());

        return (new ProveedorResource($proveedor))
            ->additional(['message' => 'Proveedor actualizado exitosamente']);
    }

    /**
     * DELETE /api/proveedores/{id}
     * Eliminar un proveedor (soft delete)
     */
    #[OA\Delete(
        path: '/api/proveedores/{id}',
        summary: 'Eliminar un proveedor',
        description: 'Realiza un soft delete del proveedor',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Proveedor eliminado exitosamente'),
            new OA\Response(response: 404, description: 'Proveedor no encontrado')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $proveedor = Proveedor::findOrFail($id);
        $this->authorize('delete', $proveedor);

        $this->proveedorService->eliminar($proveedor);

        return response()->json(['message' => 'Proveedor eliminado exitosamente']);
    }
}
