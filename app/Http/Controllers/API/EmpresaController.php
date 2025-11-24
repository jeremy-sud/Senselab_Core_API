<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use App\Http\Requests\StoreEmpresaRequest;
use App\Http\Requests\UpdateEmpresaRequest;
use App\Http\Resources\EmpresaResource;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class EmpresaController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['empresas', 'tenants'];
    protected $cacheTTL = 3600; // 1 hora
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: '/api/empresas',
        summary: 'Listar empresas',
        description: 'Obtiene el listado paginado de empresas (tenants) con búsqueda opcional',
        security: [['sanctum' => []]],
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15, example: 15)
            ),
            new OA\Parameter(
                name: 'search',
                description: 'Búsqueda por nombre, razón social, identificación o email',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'Sistemas')
            ),
            new OA\Parameter(
                name: 'activos',
                description: 'Filtrar solo empresas activas',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado de empresas obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Empresa')
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer')
                            ],
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Empresa::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            
            $empresas = $this->cacheQueryIfEnabled(
                $this->getCacheKey('index', $request->all()),
                function() use ($request, $perPage, $search) {
                    $query = Empresa::with(['regimenTributario']);
                    
                    if ($search) {
                        $query->where(function($q) use ($search) {
                            $q->where('nombre', 'like', "%{$search}%")
                              ->orWhere('razon_social', 'like', "%{$search}%")
                              ->orWhere('num_identificacion_dgt', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        });
                    }
                    
                    if ($request->boolean('activos')) {
                        $query->where('activo', true);
                    }
                    
                    return $query->orderBy('creado_en', 'desc')->paginate($perPage);
                }
            );
            
            return EmpresaResource::collection($empresas);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener empresas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreEmpresaRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: '/api/empresas',
        summary: 'Crear empresa',
        description: 'Registra una nueva empresa (tenant) en el sistema multi-tenant',
        security: [['sanctum' => []]],
        tags: ['Empresas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'num_identificacion_dgt', 'regimen_tributario_id'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Sistemas Ursol S.A.'),
                    new OA\Property(property: 'razon_social', type: 'string', nullable: true, example: 'Sistemas Ursol Sociedad Anónima'),
                    new OA\Property(property: 'num_identificacion_dgt', type: 'string', example: '3-101-123456'),
                    new OA\Property(property: 'regimen_tributario_id', type: 'integer', example: 1),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'info@ursol.com'),
                    new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Escazú'),
                    new OA\Property(property: 'activo', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Empresa creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Empresa'),
                        new OA\Property(property: 'message', type: 'string', example: 'Empresa creada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function store(StoreEmpresaRequest $request)
    {
        $this->authorize('create', Empresa::class);
        
        try {
            $empresa = Empresa::create($request->validated());
            $empresa->load('regimenTributario');
            
            $this->flushCache();
            
            return (new EmpresaResource($empresa))
                ->additional(['message' => 'Empresa creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear empresa',
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
        path: '/api/empresas/{id}',
        summary: 'Obtener empresa',
        description: 'Obtiene los detalles de una empresa incluyendo sucursales, usuarios y configuraciones',
        security: [['sanctum' => []]],
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la empresa',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Empresa obtenida exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Empresa')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Empresa no encontrada'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function show(int $id)
    {
        try {
            $empresa = Empresa::with([
                'regimenTributario',
                'sucursales',
                'usuarios',
                'configuraciones'
            ])->findOrFail($id);
            
            $this->authorize('view', $empresa);
            
            return new EmpresaResource($empresa);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateEmpresaRequest $request
     * @param Empresa $empresa
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/api/empresas/{empresa}',
        summary: 'Actualizar empresa',
        description: 'Actualiza la información de una empresa existente',
        security: [['sanctum' => []]],
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(
                name: 'empresa',
                description: 'ID de la empresa',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Sistemas Ursol S.A.'),
                    new OA\Property(property: 'razon_social', type: 'string', nullable: true),
                    new OA\Property(property: 'telefono', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Empresa actualizada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Empresa'),
                        new OA\Property(property: 'message', type: 'string', example: 'Empresa actualizada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Empresa no encontrada'),
            new OA\Response(response: 422, description: 'Errores de validación'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function update(UpdateEmpresaRequest $request, Empresa $empresa)
    {
        $this->authorize('update', $empresa);
        
        try {
            $empresa->update($request->validated());
            $empresa->load('regimenTributario');
            
            $this->flushCache();
            
            return (new EmpresaResource($empresa))
                ->additional(['message' => 'Empresa actualizada exitosamente']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar empresa',
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
        path: '/api/empresas/{id}',
        summary: 'Eliminar empresa',
        description: 'Realiza soft delete de una empresa (marca como inactiva y eliminada)',
        security: [['sanctum' => []]],
        tags: ['Empresas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la empresa',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Empresa eliminada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Empresa eliminada exitosamente')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Empresa no encontrada'),
            new OA\Response(response: 500, description: 'Error del servidor')
        ]
    )]
    public function destroy(int $id)
    {
        try {
            $empresa = Empresa::findOrFail($id);
            
            $this->authorize('delete', $empresa);
            
            // Soft delete - marcar como inactivo
            $empresa->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            $this->flushCache();
            
            return response()->json([
                'message' => 'Empresa eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Empresa no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
