<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use App\Services\AlmacenService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAlmacenRequest;
use App\Http\Requests\UpdateAlmacenRequest;
use App\Http\Resources\AlmacenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * AlmacenController - Versión Refactorizada (FASE 8)
 *
 * Controlador simplificado usando Service Layer Pattern.
 * Delegación: Validación (FormRequest) → Service → Response
 *
 * Reducción de líneas: 274 → ~120 (-56%)
 * Refactorización completada: FASE 8
 */
class AlmacenController extends Controller
{
    public function __construct(private AlmacenService $almacenService) {}

    /**
     * GET /api/almacenes
     * Listar almacenes con filtros opcionales
     */
    #[OA\Get(
        path: '/api/almacenes',
        summary: 'Listar almacenes',
        description: 'Lista almacenes/bodegas con filtros por empresa y sucursal',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'empresa_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sucursal_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'activos', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Listado exitoso', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Almacen'))]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Almacen::class);

        $almacenes = $this->almacenService->listar(
            filtros: $request->only(['empresa_id', 'sucursal_id', 'activos']),
            perPage: (int) $request->input('per_page', 15)
        );

        return AlmacenResource::collection($almacenes);
    }

    /**
     * POST /api/almacenes
     * Crear un nuevo almacén
     */
    #[OA\Post(
        path: '/api/almacenes',
        summary: 'Crear almacén',
        description: 'Crea un almacén/bodega. Si es principal, desmarca otros de la sucursal',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'sucursal_id', 'nombre'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer'),
                    new OA\Property(property: 'sucursal_id', type: 'integer'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Bodega Principal'),
                    new OA\Property(property: 'codigo', type: 'string', nullable: true),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'ubicacion', type: 'string', nullable: true),
                    new OA\Property(property: 'es_principal', type: 'boolean')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Almacén creado')]
    )]
    public function store(StoreAlmacenRequest $request): JsonResponse
    {
        $this->authorize('create', Almacen::class);

        $almacen = $this->almacenService->crear($request->validated());

        return (new AlmacenResource($almacen))
            ->additional(['message' => 'Almacén creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/almacenes/{id}
     * Obtener un almacén específico
     */
    #[OA\Get(
        path: '/api/almacenes/{id}',
        summary: 'Obtener almacén',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Almacén encontrado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(int $id): AlmacenResource
    {
        $almacen = $this->almacenService->obtener($id);
        $this->authorize('view', $almacen);

        return new AlmacenResource($almacen);
    }

    /**
     * PUT /api/almacenes/{id}
     * Actualizar un almacén existente
     */
    #[OA\Put(
        path: '/api/almacenes/{id}',
        summary: 'Actualizar almacén',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
    public function update(UpdateAlmacenRequest $request, int $id): AlmacenResource
    {
        $almacen = Almacen::findOrFail($id);
        $this->authorize('update', $almacen);

        $almacen = $this->almacenService->actualizar($almacen, $request->validated());

        return (new AlmacenResource($almacen))
            ->additional(['message' => 'Almacén actualizado exitosamente']);
    }

    /**
     * DELETE /api/almacenes/{id}
     * Eliminar un almacén (soft delete)
     */
    #[OA\Delete(
        path: '/api/almacenes/{id}',
        summary: 'Eliminar almacén',
        description: 'Soft delete. No permite eliminar almacén principal',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Eliminado'),
            new OA\Response(response: 422, description: 'No se puede eliminar el principal')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $almacen = Almacen::findOrFail($id);
        $this->authorize('delete', $almacen);

        try {
            $this->almacenService->eliminar($almacen);

            return response()->json(['message' => 'Almacén eliminado exitosamente']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
