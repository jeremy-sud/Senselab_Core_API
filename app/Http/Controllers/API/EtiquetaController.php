<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Etiqueta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class EtiquetaController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['etiquetas', 'catalogos'];
    protected $cacheTTL = 7200; // 2 horas

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/etiquetas',
        summary: 'Listar etiquetas',
        description: 'Obtiene un listado de etiquetas para clasificación',
        security: [['sanctum' => []]],
        tags: ['Etiquetas'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
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
        $this->authorize('viewAny', Etiqueta::class);

        $cacheKey = $this->generateCacheKey('etiquetas.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = Etiqueta::with('empresa')->activas();

            if ($request->filled('nombre')) {
                $query->porNombre($request->nombre);
            }

            $etiquetas = $query->orderBy('nombre')->paginate($perPage);

            return response()->json($etiquetas);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/etiquetas',
        summary: 'Crear etiqueta',
        description: 'Crea una nueva etiqueta para clasificación',
        security: [['sanctum' => []]],
        tags: ['Etiquetas'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Urgente'),
                    new OA\Property(property: 'color_hex', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$', example: '#FF0000'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Etiqueta creada exitosamente',
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
        $this->authorize('create', Etiqueta::class);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        DB::beginTransaction();
        try {
            $validated['empresa_id'] = auth()->user()->empresa_id;
            
            $etiqueta = Etiqueta::create($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Etiqueta creada exitosamente',
                'data' => $etiqueta->load('empresa')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear etiqueta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/etiquetas/{id}',
        summary: 'Obtener etiqueta específica',
        description: 'Obtiene los detalles de una etiqueta',
        security: [['sanctum' => []]],
        tags: ['Etiquetas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la etiqueta',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Etiqueta obtenida exitosamente'
            )
        ]
    )]
    public function show(string $id)
    {
        $etiqueta = Etiqueta::with('empresa')->findOrFail($id);
        $this->authorize('view', $etiqueta);

        $cacheKey = $this->generateCacheKey("etiquetas.show.{$id}");

        return $this->getCached($cacheKey, function () use ($etiqueta) {
            return response()->json(['data' => $etiqueta]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/etiquetas/{id}',
        summary: 'Actualizar etiqueta',
        description: 'Actualiza información de una etiqueta',
        security: [['sanctum' => []]],
        tags: ['Etiquetas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la etiqueta',
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
                    new OA\Property(property: 'color_hex', type: 'string', pattern: '^#[0-9A-Fa-f]{6}$'),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Etiqueta actualizada exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);
        $this->authorize('update', $etiqueta);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'activo' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            $etiqueta->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Etiqueta actualizada exitosamente',
                'data' => $etiqueta->fresh('empresa')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar etiqueta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/etiquetas/{id}',
        summary: 'Eliminar etiqueta',
        description: 'Elimina (soft delete) una etiqueta',
        security: [['sanctum' => []]],
        tags: ['Etiquetas'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID de la etiqueta',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Etiqueta eliminada exitosamente'
            )
        ]
    )]
    public function destroy(string $id)
    {
        $etiqueta = Etiqueta::findOrFail($id);
        $this->authorize('delete', $etiqueta);

        DB::beginTransaction();
        try {
            $etiqueta->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Etiqueta eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar etiqueta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
