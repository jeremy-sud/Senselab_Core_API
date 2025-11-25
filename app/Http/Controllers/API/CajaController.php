<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class CajaController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['cajas', 'sucursales', 'configuracion'];
    protected $cacheTTL = 3600; // 1 hora

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/cajas',
        summary: 'Listar cajas registradoras',
        description: 'Obtiene un listado paginado de cajas',
        security: [['sanctum' => []]],
        tags: ['Cajas'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'sucursal_id',
                description: 'Filtrar por sucursal',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'nombre',
                description: 'Buscar por nombre',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $this->authorize('viewAny', Caja::class);

        $cacheKey = $this->generateCacheKey('cajas.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = Caja::with(['sucursal'])->activas();

            if ($request->filled('sucursal_id')) {
                $query->porSucursal($request->sucursal_id);
            }

            if ($request->filled('nombre')) {
                $query->porNombre($request->nombre);
            }

            $cajas = $query->orderBy('id')->paginate($perPage);

            return response()->json($cajas);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/cajas',
        summary: 'Crear caja registradora',
        description: 'Crea una nueva caja para una sucursal',
        security: [['sanctum' => []]],
        tags: ['Cajas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sucursal_id', 'nombre'],
                properties: [
                    new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Caja 1'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Caja principal mostrador'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Caja creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request)
    {
        $this->authorize('create', Caja::class);

        $validated = $request->validate([
            'sucursal_id' => 'required|exists:sucursales,id',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $caja = Caja::create($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Caja creada exitosamente',
                'data' => $caja->load('sucursal')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/cajas/{id}',
        summary: 'Obtener caja específica',
        description: 'Obtiene los detalles de una caja',
        security: [['sanctum' => []]],
        tags: ['Cajas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la caja',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Caja obtenida exitosamente'
            )
        ]
    )]
    public function show(string $id)
    {
        $caja = Caja::with('sucursal')->findOrFail($id);
        $this->authorize('view', $caja);

        $cacheKey = $this->generateCacheKey("cajas.show.{$id}");

        return $this->getCached($cacheKey, function () use ($caja) {
            return response()->json(['data' => $caja]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/cajas/{id}',
        summary: 'Actualizar caja',
        description: 'Actualiza información de una caja',
        security: [['sanctum' => []]],
        tags: ['Cajas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la caja',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string'),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Caja actualizada exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('update', $caja);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            $caja->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Caja actualizada exitosamente',
                'data' => $caja->fresh('sucursal')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/cajas/{id}',
        summary: 'Eliminar caja',
        description: 'Elimina (soft delete) una caja',
        security: [['sanctum' => []]],
        tags: ['Cajas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la caja',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Caja eliminada exitosamente'
            )
        ]
    )]
    public function destroy(string $id)
    {
        $caja = Caja::findOrFail($id);
        $this->authorize('delete', $caja);

        DB::beginTransaction();
        try {
            $caja->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Caja eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar caja',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
