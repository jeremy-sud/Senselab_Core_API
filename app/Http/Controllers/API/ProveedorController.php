<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProveedorRequest;
use App\Http\Requests\UpdateProveedorRequest;
use App\Http\Resources\ProveedorResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class ProveedorController extends Controller
{
    use HasCacheableQueries;

    protected $cacheTags = ['proveedores', 'catalogos'];
    protected $cacheTTL = 1800; // 30 minutos
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/proveedores',
        summary: 'Listar proveedores',
        description: 'Obtiene un listado paginado de proveedores con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, example: 15)
            ),
            new OA\Parameter(
                name: 'page',
                description: 'Número de página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1)
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Búsqueda por nombre, nombre comercial, número de identificación o email',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Distribuidora')
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
                description: 'Filtrar solo proveedores activos',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de proveedores obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Proveedor')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 3),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 42)
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener proveedores'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Proveedor::class);

        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $empresaId = $request->input('empresa_id');

            $proveedores = $this->cacheQueryIfEnabled(
                $this->getCacheKey('index', $request->all()),
                function() use ($request, $perPage, $search, $empresaId) {
                    $query = Proveedor::with('empresa');

                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('nombre', 'like', "%{$search}%")
                              ->orWhere('nombre_comercial', 'like', "%{$search}%")
                              ->orWhere('numero_identificacion', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        });
                    }

                    if ($empresaId) {
                        $query->where('empresa_id', $empresaId);
                    }

                    if ($request->boolean('activos')) {
                        $query->where('activo', true);
                    }

                    return $query->orderBy('id', 'asc')->paginate($perPage);
                }
            );

            return ProveedorResource::collection($proveedores);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProveedorRequest $request
     * @return \Illuminate\Http\JsonResponse
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
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'DINASA'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'ventas@dinasa.com'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Curridabat'),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
                    new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Curridabat'),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'Curridabat'),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 1000000.00),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 60),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Proveedor creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proveedor'),
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor creado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al crear proveedor'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function store(StoreProveedorRequest $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('create', Proveedor::class);

        try {
            $proveedor = Proveedor::create($request->validated());
            $proveedor->load('empresa');

            $this->flushCache();

            return (new ProveedorResource($proveedor))
                ->additional(['message' => 'Proveedor creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return ProveedorResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/proveedores/{id}',
        summary: 'Obtener un proveedor específico',
        description: 'Obtiene los detalles de un proveedor por su ID, incluyendo sus últimas 10 órdenes de compra y cuentas por pagar pendientes',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del proveedor',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proveedor obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proveedor')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Proveedor no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor no encontrado')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener proveedor'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function show(int $id): ProveedorResource
    {
        try {
            $proveedor = Proveedor::with([
                'empresa',
                'ordenesCompra' => function($query) {
                    $query->latest()->limit(10);
                },
                'cuentasPorPagar' => function($query) {
                    $query->where('estado', 'pendiente');
                }
            ])->findOrFail($id);

            $this->authorize('view', $proveedor);

            return new ProveedorResource($proveedor);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProveedorRequest $request
     * @param int $id
     * @return ProveedorResource|\Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/api/proveedores/{id}',
        summary: 'Actualizar un proveedor',
        description: 'Actualiza la información de un proveedor existente',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del proveedor',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05'], example: '02'),
                    new OA\Property(property: 'numero_identificacion', type: 'string', example: '3-101-123456'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Distribuidora Nacional S.A.'),
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'DINASA'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'ventas@dinasa.com'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Curridabat'),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
                    new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Curridabat'),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'Curridabat'),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 1000000.00),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 60),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proveedor actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Proveedor'),
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor actualizado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Proveedor no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor no encontrado')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos'),
                        new OA\Property(property: 'errors', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al actualizar proveedor'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function update(UpdateProveedorRequest $request, int $id): ProveedorResource
    {
        try {
            $proveedor = Proveedor::findOrFail($id);

            $this->authorize('update', $proveedor);

            $proveedor->update($request->validated());
            $proveedor->load('empresa');

            $this->flushCache();

            return (new ProveedorResource($proveedor))
                ->additional(['message' => 'Proveedor actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/proveedores/{id}',
        summary: 'Eliminar un proveedor',
        description: 'Realiza un soft delete del proveedor, marcándolo como inactivo y eliminado',
        security: [['sanctum' => []]],
        tags: ['Proveedores'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del proveedor',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Proveedor eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor eliminado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Proveedor no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Proveedor no encontrado')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al eliminar proveedor'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $proveedor = Proveedor::findOrFail($id);

            $this->authorize('delete', $proveedor);

            // Soft delete
            $proveedor->update([
                'activo' => false,
                'eliminado' => true
            ]);

            $this->flushCache();

            return response()->json([
                'message' => 'Proveedor eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Proveedor no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
