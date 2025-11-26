<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RegimenTributario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RegimenTributarioController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['regimenes_tributarios', 'configuracion'];
    protected $cacheTTL = 86400; // 24 horas (datos muy estables)

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/regimenes-tributarios',
        summary: 'Listar regímenes tributarios',
        description: 'Obtiene un listado de regímenes tributarios disponibles',
        security: [['sanctum' => []]],
        tags: ['Regímenes Tributarios'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', RegimenTributario::class);

        $cacheKey = $this->generateCacheKey('regimenes_tributarios.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $regimenes = RegimenTributario::activos()
                ->noEliminados()
                ->orderBy('nombre')
                ->paginate($perPage);

            return response()->json($regimenes);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/regimenes-tributarios',
        summary: 'Crear régimen tributario',
        description: 'Crea un nuevo régimen tributario',
        security: [['sanctum' => []]],
        tags: ['Regímenes Tributarios'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['codigo', 'nombre'],
                properties: [
                    new OA\Property(property: 'codigo', type: 'string', example: 'ST', description: 'Código del régimen'),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Simplified Tax'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Régimen simplificado tributario'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Régimen creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', RegimenTributario::class);

        $validated = $request->validate([
            'codigo' => 'required|string|max:10|unique:regimenes_tributarios,codigo',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $regimen = RegimenTributario::create($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Régimen tributario creado exitosamente',
                'data' => $regimen
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear régimen tributario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/regimenes-tributarios/{id}',
        summary: 'Obtener régimen tributario específico',
        description: 'Obtiene los detalles de un régimen tributario',
        security: [['sanctum' => []]],
        tags: ['Regímenes Tributarios'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del régimen',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Régimen obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $regimen = RegimenTributario::findOrFail($id);
        $this->authorize('view', $regimen);

        $cacheKey = $this->generateCacheKey("regimenes_tributarios.show.{$id}");

        return $this->getCached($cacheKey, function () use ($regimen) {
            return response()->json(['data' => $regimen]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/regimenes-tributarios/{id}',
        summary: 'Actualizar régimen tributario',
        description: 'Actualiza información de un régimen tributario',
        security: [['sanctum' => []]],
        tags: ['Regímenes Tributarios'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del régimen',
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
                description: 'Régimen actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): JsonResponse
    {
        $regimen = RegimenTributario::findOrFail($id);
        $this->authorize('update', $regimen);

        $validated = $request->validate([
            'codigo' => 'sometimes|string|max:10|unique:regimenes_tributarios,codigo,' . $id,
            'nombre' => 'sometimes|string|max:100',
            'descripcion' => 'nullable|string',
            'activo' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            $regimen->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Régimen tributario actualizado exitosamente',
                'data' => $regimen->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar régimen tributario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/regimenes-tributarios/{id}',
        summary: 'Eliminar régimen tributario',
        description: 'Elimina (soft delete) un régimen tributario',
        security: [['sanctum' => []]],
        tags: ['Regímenes Tributarios'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del régimen',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Régimen eliminado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $regimen = RegimenTributario::findOrFail($id);
        $this->authorize('delete', $regimen);

        DB::beginTransaction();
        try {
            $regimen->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Régimen tributario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar régimen tributario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
