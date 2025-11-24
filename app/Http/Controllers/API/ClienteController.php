<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\Request;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Http\Resources\ClienteResource;
use OpenApi\Attributes as OA;

class ClienteController extends Controller
{
    use HasCacheableQueries;

    /**
     * Tags de cache para clientes
     * @var array
     */
    protected array $cacheTags = ['clientes', 'catalogos'];

    /**
     * TTL del cache: 30 minutos (clientes cambian con frecuencia)
     * @var int
     */
    protected int $cacheTTL = 1800;
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/clientes',
        summary: 'Listar clientes',
        description: 'Obtiene un listado paginado de clientes con filtros opcionales',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
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
                description: 'Búsqueda por nombre, apellidos, nombre comercial, número de identificación o email',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Juan')
            ),
            new OA\Parameter(
                name: 'empresa_id',
                description: 'Filtrar por empresa',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'tipo_identificacion',
                description: 'Filtrar por tipo de identificación',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['01', '02', '03', '04', '05', '06', '07'], example: '01')
            ),
            new OA\Parameter(
                name: 'activos',
                description: 'Filtrar solo clientes activos',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de clientes obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Cliente')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 67)
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
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener clientes'),
                        new OA\Property(property: 'error', type: 'string', example: 'Database connection failed')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Cliente::class);
        
        try {
            // Usar cache si está habilitado
            return $this->cacheQueryIfEnabled($request, function() use ($request) {
                $perPage = $request->input('per_page', 15);
                $search = $request->input('search');
                $empresaId = $request->input('empresa_id');
                $tipoIdentificacion = $request->input('tipo_identificacion');
                
                $query = Cliente::with('empresa');
                
                if ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('nombre', 'like', "%{$search}%")
                          ->orWhere('apellidos', 'like', "%{$search}%")
                          ->orWhere('nombre_comercial', 'like', "%{$search}%")
                          ->orWhere('numero_identificacion', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }
                
                if ($empresaId) {
                    $query->where('empresa_id', $empresaId);
                }
                
                if ($tipoIdentificacion) {
                    $query->porTipoIdentificacion($tipoIdentificacion);
                }
                
                if ($request->boolean('activos')) {
                    $query->activos();
                }
                
                $clientes = $query->orderBy('id', 'asc')
                                  ->cursorPaginate($perPage);
                
                return ClienteResource::collection($clientes);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener clientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreClienteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/clientes',
        summary: 'Crear un nuevo cliente',
        description: 'Registra un nuevo cliente en el sistema',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05', '06', '07'], example: '01'),
                    new OA\Property(property: 'numero_identificacion', type: 'string', example: '1-2345-6789'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellidos', type: 'string', nullable: true, example: 'Pérez González'),
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'Comercial JP'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'juan.perez@example.com'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '8888-7777'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Escazú, del mall 200m oeste'),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
                    new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Escazú'),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'San Rafael'),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 500000.00),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 30),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cliente creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cliente'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente creado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Errores de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Los datos proporcionados no son válidos'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: ['numero_identificacion' => ['El número de identificación ya está en uso']]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al crear cliente'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function store(StoreClienteRequest $request)
    {
        $this->authorize('create', Cliente::class);
        
        try {
            $cliente = Cliente::create($request->validated());
            $cliente->load('empresa');
            
            // Invalidar cache de clientes
            $this->flushCache();
            
            return (new ClienteResource($cliente))
                ->additional(['message' => 'Cliente creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/clientes/{id}',
        summary: 'Obtener un cliente específico',
        description: 'Obtiene los detalles de un cliente por su ID, incluyendo sus últimas 10 ventas y cuentas por cobrar pendientes',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del cliente',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cliente obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cliente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente no encontrado')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al obtener cliente'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function show(int $id)
    {
        try {
            $cliente = Cliente::with([
                'empresa',
                'ventas' => function($query) {
                    $query->latest()->limit(10);
                },
                'cuentasPorCobrar' => function($query) {
                    $query->where('estado', 'pendiente');
                }
            ])->findOrFail($id);
            
            $this->authorize('view', $cliente);
            
            return new ClienteResource($cliente);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateClienteRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/api/clientes/{id}',
        summary: 'Actualizar un cliente',
        description: 'Actualiza la información de un cliente existente',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del cliente',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05', '06', '07'], example: '01'),
                    new OA\Property(property: 'numero_identificacion', type: 'string', example: '1-2345-6789'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellidos', type: 'string', nullable: true, example: 'Pérez González'),
                    new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'Comercial JP'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'juan.perez@example.com'),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '8888-7777'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Escazú, del mall 200m oeste'),
                    new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
                    new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Escazú'),
                    new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'San Rafael'),
                    new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 500000.00),
                    new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 30),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cliente actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Cliente'),
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente actualizado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente no encontrado')
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
                        new OA\Property(property: 'message', type: 'string', example: 'Error al actualizar cliente'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function update(UpdateClienteRequest $request, int $id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            
            $this->authorize('update', $cliente);
            
            $cliente->update($request->validated());
            $cliente->load('empresa');
            
            // Invalidar cache de clientes
            $this->flushCache();
            
            return (new ClienteResource($cliente))
                ->additional(['message' => 'Cliente actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Delete(
        path: '/api/clientes/{id}',
        summary: 'Eliminar un cliente',
        description: 'Realiza un soft delete del cliente, marcándolo como inactivo y eliminado',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del cliente',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cliente eliminado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente eliminado exitosamente')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Cliente no encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cliente no encontrado')
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Error del servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Error al eliminar cliente'),
                        new OA\Property(property: 'error', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function destroy(int $id)
    {
        try {
            $cliente = Cliente::findOrFail($id);
            
            $this->authorize('delete', $cliente);
            
            // Soft delete - marcar como inactivo
            $cliente->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            // Invalidar cache de clientes
            $this->flushCache();
            
            return response()->json([
                'message' => 'Cliente eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
