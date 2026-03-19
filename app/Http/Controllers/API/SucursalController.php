<?php

namespace App\Http\Controllers\API;

use App\DTOs\API\SucursalCreateDTO;
use App\DTOs\API\SucursalUpdateDTO;
use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSucursalRequest;
use App\Http\Requests\UpdateSucursalRequest;
use App\Http\Resources\SucursalResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\SucursalService;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class SucursalController extends Controller
{
    use HasCacheableQueries;

    /** @var array<int, string> */
    protected array $cacheTags = ['sucursales', 'catalogos'];
    protected int $cacheTTL = 3600; // 1 hora

    public function __construct(
        private readonly SucursalService $service
    ) {}
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection|JsonResponse
     */
    #[OA\Get(
        path: '/api/sucursales',
        summary: 'Listar sucursales',
        description: 'Obtiene listado paginado de sucursales filtradas por empresa',
        security: [['sanctum' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'empresa_id',
                description: 'Filtrar por empresa',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'activos',
                description: 'Solo sucursales activas',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Sucursal'))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $this->authorize('viewAny', Sucursal::class);

        $sucursales = $this->cacheQueryIfEnabled(
            $this->getCacheKey('index', $request->all()),
            fn () => $this->service->listar($request->all())
        );

        return SucursalResource::collection($sucursales);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreSucursalRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/sucursales',
        summary: 'Crear sucursal',
        description: 'Crea una nueva sucursal. Si es principal, desmarca las demás',
        security: [['sanctum' => []]],
        tags: ['Sucursales'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'nombre'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Sucursal Centro'),
                    new OA\Property(property: 'codigo', type: 'string', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', nullable: true),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true),
                    new OA\Property(property: 'canton', type: 'string', nullable: true),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true),
                    new OA\Property(property: 'es_principal', type: 'boolean', example: false),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Sucursal creada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Sucursal'),
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function store(StoreSucursalRequest $request): JsonResponse
    {
        $this->authorize('create', Sucursal::class);

        $sucursal = $this->service->crear(SucursalCreateDTO::fromRequest($request)->toArray());

        $this->flushCache();

        return (new SucursalResource($sucursal))
            ->additional(['message' => 'Sucursal creada exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return SucursalResource
     */
    #[OA\Get(
        path: '/api/sucursales/{id}',
        summary: 'Obtener sucursal',
        description: 'Detalles de una sucursal con almacenes y cajas',
        security: [['sanctum' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucursal encontrada',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Sucursal')])
            ),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function show(int $id): SucursalResource
    {
        $sucursal = $this->service->obtener($id);

        $this->authorize('view', $sucursal);

        return new SucursalResource($sucursal);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateSucursalRequest $request
     * @param int $id
     * @return SucursalResource
     */
    #[OA\Put(
        path: '/api/sucursales/{id}',
        summary: 'Actualizar sucursal',
        description: 'Actualiza datos de sucursal. Si se marca como principal, desmarca las demás',
        security: [['sanctum' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'es_principal', type: 'boolean'),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Actualizada exitosamente'),
            new OA\Response(response: 404, description: 'No encontrada')
        ]
    )]
    public function update(UpdateSucursalRequest $request, int $id): SucursalResource
    {
        $sucursal = Sucursal::findOrFail($id);

        $this->authorize('update', $sucursal);

        $sucursal = $this->service->actualizar($sucursal, SucursalUpdateDTO::fromRequest($request)->toArray());

        $this->flushCache();

        return (new SucursalResource($sucursal))
            ->additional(['message' => 'Sucursal actualizada exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/sucursales/{id}',
        summary: 'Eliminar sucursal',
        description: 'Soft delete. No permite eliminar la sucursal principal',
        security: [['sanctum' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Eliminada exitosamente'),
            new OA\Response(response: 404, description: 'No encontrada'),
            new OA\Response(response: 422, description: 'No se puede eliminar la principal')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $sucursal = Sucursal::findOrFail($id);

        $this->authorize('delete', $sucursal);

        $this->service->eliminar($sucursal);

        $this->flushCache();

        return $this->deletedResponse('Sucursal eliminada exitosamente');
    }
}
