<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoFe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use App\Http\Resources\ConsecutivoFeResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ConsecutivoFeController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['consecutivos_fe', 'facturacion_electronica', 'dgt'];
    protected $cacheTTL = 3600; // 1 hora (datos críticos pero estables)

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/consecutivos-fe',
        summary: 'Listar consecutivos de facturación electrónica',
        description: 'Obtiene un listado de consecutivos autorizados por DGT',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'empresa_id',
                description: 'Filtrar por empresa',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'tipo_comprobante',
                description: 'Filtrar por tipo de comprobante',
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ConsecutivoFe::class);

        $cacheKey = $this->generateCacheKey('consecutivos_fe.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = ConsecutivoFe::with(['empresa', 'sucursal'])
                ->activos();

            if ($request->filled('empresa_id')) {
                $query->where('empresa_id', $request->empresa_id);
            }

            if ($request->filled('tipo_comprobante')) {
                $query->porTipoComprobante($request->tipo_comprobante);
            }

            $consecutivos = $query->orderBy('id', 'desc')->paginate($perPage);

            return ConsecutivoFeResource::collection($consecutivos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/consecutivos-fe',
        summary: 'Crear consecutivo de FE',
        description: 'Crea un nuevo consecutivo autorizado por DGT (CRÍTICO - Cumplimiento legal)',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['empresa_id', 'sucursal_id', 'tipo_comprobante', 'prefijo', 'consecutivo_inicial', 'consecutivo_final'],
                properties: [
                    new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tipo_comprobante', type: 'string', example: '01', description: '01=FE, 04=TE, etc'),
                    new OA\Property(property: 'prefijo', type: 'string', example: '00100001'),
                    new OA\Property(property: 'consecutivo_inicial', type: 'integer', example: 1),
                    new OA\Property(property: 'consecutivo_final', type: 'integer', example: 100000),
                    new OA\Property(property: 'fecha_resolucion', type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'numero_resolucion', type: 'string', example: 'DGT-R-001-2024'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Consecutivo creado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(Request $request): ConsecutivoFeResource|JsonResponse
    {
        $this->authorize('create', ConsecutivoFe::class);

        $validated = $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'sucursal_id' => 'required|exists:sucursales,id',
            'tipo_comprobante' => 'required|string|max:2',
            'prefijo' => 'required|string|max:20',
            'consecutivo_inicial' => 'required|integer|min:1',
            'consecutivo_final' => 'required|integer|min:1',
            'fecha_resolucion' => 'nullable|date',
            'numero_resolucion' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Validar que consecutivo_final > consecutivo_inicial
            if ($validated['consecutivo_final'] <= $validated['consecutivo_inicial']) {
                return response()->json([
                    'message' => 'El consecutivo final debe ser mayor al consecutivo inicial'
                ], 422);
            }

            // El consecutivo actual comienza en el inicial
            $validated['consecutivo_actual'] = $validated['consecutivo_inicial'];

            $consecutivo = ConsecutivoFe::create($validated);

            DB::commit();
            $this->clearCache();

            return (new ConsecutivoFeResource($consecutivo->load(['empresa', 'sucursal'])))
                ->additional(['message' => 'Consecutivo de FE creado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al crear consecutivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/consecutivos-fe/{id}',
        summary: 'Obtener consecutivo específico',
        description: 'Obtiene los detalles de un consecutivo',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del consecutivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consecutivo obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id): ConsecutivoFeResource
    {
        $consecutivo = ConsecutivoFe::with(['empresa', 'sucursal'])->findOrFail($id);
        $this->authorize('view', $consecutivo);

        $cacheKey = $this->generateCacheKey("consecutivos_fe.show.{$id}");

        return $this->getCached($cacheKey, function () use ($consecutivo) {
            return new ConsecutivoFeResource($consecutivo);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/consecutivos-fe/{id}',
        summary: 'Actualizar consecutivo',
        description: 'Actualiza información de un consecutivo (CUIDADO - operación crítica)',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del consecutivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'consecutivo_final', type: 'integer', description: 'Ampliar rango'),
                    new OA\Property(property: 'numero_resolucion', type: 'string'),
                    new OA\Property(property: 'activo', type: 'boolean')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consecutivo actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id): ConsecutivoFeResource|JsonResponse
    {
        $consecutivo = ConsecutivoFe::findOrFail($id);
        $this->authorize('update', $consecutivo);

        $validated = $request->validate([
            'consecutivo_final' => 'sometimes|integer|min:1',
            'numero_resolucion' => 'sometimes|string|max:100',
            'fecha_resolucion' => 'sometimes|date',
            'activo' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Validar que el nuevo final sea mayor al actual si se modifica
            if (isset($validated['consecutivo_final']) && $validated['consecutivo_final'] <= $consecutivo->consecutivo_actual) {
                return response()->json([
                    'message' => 'El consecutivo final debe ser mayor al consecutivo actual en uso'
                ], 422);
            }

            $consecutivo->update($validated);

            DB::commit();
            $this->clearCache();

            return (new ConsecutivoFeResource($consecutivo->fresh(['empresa', 'sucursal'])))
                ->additional(['message' => 'Consecutivo actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar consecutivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener siguiente consecutivo (método especial para facturación)
     */
    #[OA\Post(
        path: '/api/consecutivos-fe/{id}/siguiente',
        summary: 'Obtener siguiente consecutivo',
        description: 'Obtiene y reserva el siguiente número consecutivo (CRÍTICO - DGT)',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del consecutivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consecutivo obtenido exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'consecutivo', type: 'string', example: '0000000001'),
                        new OA\Property(property: 'consecutivo_completo', type: 'string', example: '001000010000000001')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Consecutivo agotado o inactivo')
        ]
    )]
    public function obtenerSiguiente(string $id): JsonResponse
    {
        $consecutivo = ConsecutivoFe::findOrFail($id);
        $this->authorize('update', $consecutivo);

        DB::beginTransaction();
        try {
            $siguiente = $consecutivo->obtenerSiguienteConsecutivo();
            $completo = $consecutivo->formatearConsecutivoCompleto($siguiente);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'consecutivo' => $siguiente,
                'consecutivo_completo' => $completo,
                'tipo_comprobante' => $consecutivo->tipo_comprobante,
                'consecutivo_actual' => $consecutivo->fresh()->consecutivo_actual
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al obtener siguiente consecutivo',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/consecutivos-fe/{id}',
        summary: 'Desactivar consecutivo',
        description: 'Desactiva un consecutivo (no elimina por cumplimiento DGT)',
        security: [['sanctum' => []]],
        tags: ['Consecutivos FE'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del consecutivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Consecutivo desactivado exitosamente'
            )
        ]
    )]
    public function destroy(string $id): JsonResponse
    {
        $consecutivo = ConsecutivoFe::findOrFail($id);
        $this->authorize('delete', $consecutivo);

        DB::beginTransaction();
        try {
            // No eliminamos, solo desactivamos por trazabilidad DGT
            $consecutivo->update([
                'activo' => false,
                'eliminado' => true
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Consecutivo desactivado exitosamente (trazabilidad DGT mantenida)'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al desactivar consecutivo',
                'error' => $e->getMessage()
            ], 500);
        }
    }}
