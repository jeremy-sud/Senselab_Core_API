<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAlmacenRequest;
use App\Http\Requests\UpdateAlmacenRequest;
use App\Http\Resources\AlmacenResource;
use OpenApi\Attributes as OA;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
    public function index(public function index(Request $request)
    {
        try {)
    {
        $this->authorize('viewAny', Almacen::class);
        
        try {
            $perPage = $request->input('per_page', 15);
            $empresaId = $request->input('empresa_id');
            $sucursalId = $request->input('sucursal_id');
            
            $query = Almacen::with(['empresa', 'sucursal']);
            
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
            
            if ($sucursalId) {
                $query->where('sucursal_id', $sucursalId);
            }
            
            if ($request->boolean('activos')) {
                $query->where('activo', true);
            }
            
            $almacenes = $query->orderBy('nombre', 'asc')
                               ->paginate($perPage);
            
            return AlmacenResource::collection($almacenes);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener almacenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * @param StoreAlmacenRequest $request
     * @return \Illuminate\Http\JsonResponse
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
    public function store(public function store(StoreAlmacenRequest $request)
    {
        try {)
    {
        $this->authorize('create', Almacen::class);
        
        try {
            // Si es principal, desmarcar otros almacenes principales de la sucursal
            if ($request->boolean('es_principal')) {
                Almacen::where('sucursal_id', $request->sucursal_id)
                       ->update(['es_principal' => false]);
            }
            
            $almacen = Almacen::create($request->validated());
            $almacen->load(['empresa', 'sucursal']);
            
            return (new AlmacenResource($almacen))
                ->additional(['message' => 'Almacén creado exitosamente'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear almacén',
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
    public function show(int $id)
    {
        try {
            $almacen = Almacen::with([
                'empresa',
                'sucursal'
            ])->findOrFail($id);
            
            return new AlmacenResource($almacen);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * @param UpdateAlmacenRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Put(
        path: '/api/almacenes/{id}',
        summary: 'Actualizar almacén',
        security: [['sanctum' => []]],
        tags: ['Almacenes'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Actualizado')]
    )]
    public function update(UpdateAlmacenRequest $request, int $id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            
            // Si es principal, desmarcar otros almacenes principales de la sucursal
            if ($request->has('es_principal') && $request->boolean('es_principal')) {
                Almacen::where('sucursal_id', $almacen->sucursal_id)
                       ->where('id', '!=', $id)
                       ->update(['es_principal' => false]);
            }
            
            $almacen->update($request->validated());
            $almacen->load(['empresa', 'sucursal']);
            
            return (new AlmacenResource($almacen))
                ->additional(['message' => 'Almacén actualizado exitosamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar almacén',
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
    public function destroy(int $id)
    {
        try {
            $almacen = Almacen::findOrFail($id);
            
            // No permitir eliminar almacén principal
            if ($almacen->es_principal) {
                return response()->json([
                    'message' => 'No se puede eliminar el almacén principal'
                ], 422);
            }
            
            // Soft delete
            $almacen->update([
                'activo' => false,
                'eliminado' => true
            ]);
            
            return response()->json([
                'message' => 'Almacén eliminado exitosamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Almacén no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar almacén',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
