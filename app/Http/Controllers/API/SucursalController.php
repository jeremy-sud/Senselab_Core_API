<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSucursalRequest;
use App\Http\Requests\UpdateSucursalRequest;
use App\Http\Resources\SucursalResource;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class SucursalController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['sucursales', 'catalogos'];
    protected $cacheTTL = 3600; // 1 hora
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
    public function index(Request $request)
    {
        $this->authorize('viewAny', Sucursal::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            
            $query = Sucursal::with('empresa');
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $sucursales = $query->orderBy('nombre', 'asc')
                                ->paginate($perPage);
            
            return SucursalResource::collection($sucursales);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener sucursales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreSucursalRequest $request
     * @return \Illuminate\Http\JsonResponse
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
    public function store(StoreSucursalRequest $request)
    {
        $this->authorize('create', Sucursal::class);
        
        try {
            // Si es principal, desmarcar otras sucursales principales
            if ($request->boolean('es_principal')) {
                Sucursal::where('empresa_id', $request->empresa_id)
                        ->update(['es_principal' => false]);
            }
            
            $sucursal = Sucursal::create($request->validated());
            $sucursal->load('empresa');
            
            return (new SucursalResource($sucursal))
                ->additional(['message' => 'Sucursal creada exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear sucursal',
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
    public function show(int $id)
    {
        try {
            $sucursal = Sucursal::with([
                'empresa',
                'almacenes',
                'cajas'
            ])->findOrFail($id);
            $this->authorize('view', $sucursal);

            return new SucursalResource($sucursal);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateSucursalRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
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
    public function update(UpdateSucursalRequest $request, int $id)
    {
        try {
            $sucursal = Sucursal::findOrFail($id);
            $this->authorize('update', $sucursal);

            // Si es principal, desmarcar otras sucursales principales
            if ($request->has('es_principal') && $request->boolean('es_principal')) {
                Sucursal::where('empresa_id', $sucursal->empresa_id)
                        ->where('id', '!=', $id)
                        ->update(['es_principal' => false]);
            }
            
            $sucursal->update($request->validated());
            $sucursal->load('empresa');
            
            return (new SucursalResource($sucursal))
                ->additional(['message' => 'Sucursal actualizada exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar sucursal',
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
    public function destroy(int $id)
    {
        try {
            $sucursal = Sucursal::findOrFail($id);
            $this->authorize('delete', $sucursal);

            // No permitir eliminar sucursal principal
            if ($sucursal->es_principal) {
                return response()->json([
                    'message' => 'No se puede eliminar la sucursal principal'
                ], 422);
            }
            
            // Soft delete
            $sucursal->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Sucursal eliminada exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sucursal no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar sucursal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
