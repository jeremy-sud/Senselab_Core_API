<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CajaChica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;
use App\Http\Resources\CajaChicaResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;

class CajaChicaController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['caja_chica', 'tesoreria', 'fondos'];
    protected $cacheTTL = 900; // 15 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/caja-chica',
        summary: 'Listar fondos de caja chica',
        description: 'Obtiene un listado paginado de fondos de caja chica',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'estado',
                description: 'Filtrar por estado',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['Abierta', 'Cerrada', 'Liquidada'])
            ),
            new OA\Parameter(
                name: 'responsable_id',
                description: 'Filtrar por responsable',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CajaChica::class);

        $cacheKey = $this->generateCacheKey('caja_chica.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = CajaChica::with(['empresa', 'responsable'])->activos();

            if ($request->filled('estado')) {
                $query->porEstado($request->estado);
            }

            if ($request->filled('responsable_id')) {
                $query->porResponsable($request->responsable_id);
            }

            $fondos = $query->orderBy('id', 'desc')->paginate($perPage);

            return CajaChicaResource::collection($fondos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/caja-chica',
        summary: 'Crear fondo de caja chica',
        description: 'Crea un nuevo fondo de caja chica',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'nombre', 'monto_inicial', 'responsable_id', 'fecha_apertura'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Fondo Caja Chica Enero 2024'),
                    new OA\Property(property: 'monto_inicial', type: 'number', format: 'decimal', example: 50000.00),
                    new OA\Property(property: 'responsable_id', type: 'integer', example: 3),
                    new OA\Property(property: 'fecha_apertura', type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'observaciones', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Fondo creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            )
        ]
    )]
    public function store(Request $request): CajaChicaResource|JsonResponse
    {
        $this->authorize('create', CajaChica::class);

        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'nombre' => 'required|string|max:200',
            'monto_inicial' => 'required|numeric|min:0',
            'responsable_id' => 'required|exists:empleados,id',
            'fecha_apertura' => 'required|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $validated['saldo_actual'] = $validated['monto_inicial'];
            $validated['estado'] = CajaChica::ESTADO_ABIERTA;

            $fondo = CajaChica::create($validated);

            DB::commit();
            $this->clearCache();

            return (new CajaChicaResource($fondo->load(['empresa', 'responsable'])))
                ->additional(['message' => 'Fondo de caja chica creado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear fondo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/caja-chica/{id}',
        summary: 'Obtener fondo específico',
        description: 'Obtiene los detalles de un fondo de caja chica',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del fondo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fondo obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id): CajaChicaResource
    {
        $fondo = CajaChica::with(['empresa', 'responsable', 'movimientos'])->findOrFail($id);
        $this->authorize('view', $fondo);

        $cacheKey = $this->generateCacheKey("caja_chica.show.{$id}");

        return $this->getCached($cacheKey, function () use ($fondo) {
            return new CajaChicaResource($fondo);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/caja-chica/{id}',
        summary: 'Actualizar fondo',
        description: 'Actualiza información de un fondo',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del fondo',
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
                    new OA\Property(property: 'observaciones', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['Abierta', 'Cerrada', 'Liquidada'])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fondo actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): CajaChicaResource|JsonResponse
    {
        $fondo = CajaChica::findOrFail($id);
        $this->authorize('update', $fondo);

        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:200',
            'observaciones' => 'nullable|string',
            'estado' => 'sometimes|in:Abierta,Cerrada,Liquidada',
            'fecha_cierre' => 'sometimes|date',
        ]);

        DB::beginTransaction();
        try {
            $fondo->update($validated);

            DB::commit();
            $this->clearCache();

            return (new CajaChicaResource($fondo->fresh(['empresa', 'responsable'])))
                ->additional(['message' => 'Fondo actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar fondo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cerrar fondo de caja chica
     */
    #[OA\Post(
        path: '/api/caja-chica/{id}/cerrar',
        summary: 'Cerrar fondo',
        description: 'Cierra un fondo de caja chica',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del fondo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fondo cerrado exitosamente'
            )
        ]
    )]
    public function cerrar(string $id): CajaChicaResource|JsonResponse
    {
        $fondo = CajaChica::findOrFail($id);
        $this->authorize('update', $fondo);

        if (!$fondo->estaAbierta()) {
            return response()->json([
                'message' => 'Solo se pueden cerrar fondos abiertos'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $fondo->update([
                'estado' => CajaChica::ESTADO_CERRADA,
                'fecha_cierre' => now()
            ]);

            DB::commit();
            $this->clearCache();

            return (new CajaChicaResource($fondo->fresh()))
                ->additional(['message' => 'Fondo cerrado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cerrar fondo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/caja-chica/{id}',
        summary: 'Eliminar fondo',
        description: 'Elimina (soft delete) un fondo',
        security: [['sanctum' => []]],
        tags: ['Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del fondo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Fondo eliminado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $fondo = CajaChica::findOrFail($id);
        $this->authorize('delete', $fondo);

        DB::beginTransaction();
        try {
            $fondo->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Fondo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar fondo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
