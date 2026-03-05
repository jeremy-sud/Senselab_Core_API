<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Productos',
    description: 'Gestión de productos del inventario'
)]
class ProductoController extends Controller
{
    public function __construct(
        private ProductoService $service
    ) {}
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: '/api/productos',
        operationId: 'getProductos',
        summary: 'Listar productos',
        description: 'Obtiene el listado de productos con filtros, búsqueda y paginación',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Búsqueda por nombre, código, código de barras o descripción',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'laptop')
            ),
            new OA\Parameter(
                name: 'empresa_id',
                in: 'query',
                description: 'Filtrar por empresa (multi-tenancy)',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'categoria_id',
                in: 'query',
                description: 'Filtrar por categoría',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'tipo',
                in: 'query',
                description: 'Filtrar por tipo de producto',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['PRODUCTO', 'SERVICIO', 'COMBO'], example: 'PRODUCTO')
            ),
            new OA\Parameter(
                name: 'activos',
                in: 'query',
                description: 'Filtrar solo productos activos',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Cantidad de resultados por página',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 15, default: 15)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de productos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Producto')
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'string'),
                                new OA\Property(property: 'last', type: 'string'),
                                new OA\Property(property: 'prev', type: 'string', nullable: true),
                                new OA\Property(property: 'next', type: 'string', nullable: true)
                            ]
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Producto::class);

        $productos = $this->service->listar(
            $request->only(['search', 'empresa_id', 'categoria_id', 'tipo', 'activo', 'activos']),
            (int) $request->input('per_page', 15)
        );

        return ProductoResource::collection($productos);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/productos',
        operationId: 'createProducto',
        summary: 'Crear producto',
        description: 'Crea un nuevo producto en el sistema',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'codigo', 'empresa_id', 'tipo'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Laptop Dell Inspiron'),
                    new OA\Property(property: 'codigo', type: 'string', example: 'PROD-001'),
                    new OA\Property(property: 'codigo_barras', type: 'string', nullable: true, example: '1234567890123'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Laptop 15 pulgadas, 8GB RAM'),
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'categoria_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['PRODUCTO', 'SERVICIO', 'COMBO'], example: 'PRODUCTO'),
                    new OA\Property(property: 'precio_compra', type: 'number', format: 'decimal', nullable: true, example: 450000.00),
                    new OA\Property(property: 'precio_venta', type: 'number', format: 'decimal', example: 650000.00),
                    new OA\Property(property: 'unidad_medida_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'marca_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'impuesto_id', type: 'integer', nullable: true, example: 1),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Producto creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Producto'),
                        new OA\Property(property: 'message', type: 'string', example: 'Producto creado exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function store(StoreProductoRequest $request): JsonResponse
    {
        $this->authorize('create', Producto::class);

        $producto = $this->service->crear($request->validated());

        return (new ProductoResource($producto))
            ->additional(['message' => 'Producto creado exitosamente'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return ProductoResource
     */
    #[OA\Get(
        path: '/api/productos/{id}',
        operationId: 'getProducto',
        summary: 'Obtener producto',
        description: 'Obtiene los detalles de un producto específico',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del producto',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Producto')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function show(int $id): ProductoResource
    {
        $producto = $this->service->obtener($id);
        $this->authorize('view', $producto);
        return new ProductoResource($producto);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductoRequest $request
     * @param int $id
     * @return ProductoResource
     */
    #[OA\Put(
        path: '/api/productos/{id}',
        operationId: 'updateProducto',
        summary: 'Actualizar producto',
        description: 'Actualiza los datos de un producto existente',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del producto',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Laptop Dell Inspiron Actualizada'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Laptop 15 pulgadas, 16GB RAM'),
                    new OA\Property(property: 'precio_compra', type: 'number', format: 'decimal', nullable: true, example: 500000.00),
                    new OA\Property(property: 'precio_venta', type: 'number', format: 'decimal', example: 700000.00),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Producto'),
                        new OA\Property(property: 'message', type: 'string', example: 'Producto actualizado exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function update(UpdateProductoRequest $request, int $id): ProductoResource
    {
        $producto = Producto::findOrFail($id);
        $this->authorize('update', $producto);

        $producto = $this->service->actualizar($producto, $request->validated());

        return (new ProductoResource($producto))
            ->additional(['message' => 'Producto actualizado exitosamente']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/productos/{id}',
        operationId: 'deleteProducto',
        summary: 'Eliminar producto',
        description: 'Elimina (soft delete) un producto marcándolo como inactivo y eliminado',
        security: [['sanctum' => []]],
        tags: ['Productos'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: 'ID del producto',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Producto eliminado exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Producto no encontrado'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $producto = Producto::findOrFail($id);
        $this->authorize('delete', $producto);

        $this->service->eliminar($producto);

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }
}
