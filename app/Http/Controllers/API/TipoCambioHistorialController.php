<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TipoCambioHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class TipoCambioHistorialController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['tipos_cambio_historial', 'finanzas'];
    protected $cacheTTL = 3600; // 1 hora

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/tipos-cambio-historial',
        summary: 'Listar histórico de tipos de cambio',
        description: 'Obtiene un histórico de tipos de cambio entre monedas',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'moneda_origen',
                description: 'Filtrar por moneda origen',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'USD')
            ),
            new OA\Parameter(
                name: 'moneda_destino',
                description: 'Filtrar por moneda destino',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', example: 'CRC')
            ),
            new OA\Parameter(
                name: 'fecha_inicio',
                description: 'Fecha inicial del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_fin',
                description: 'Fecha final del rango',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
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
        $this->authorize('viewAny', TipoCambioHistorial::class);

        $cacheKey = $this->generateCacheKey('tipos_cambio_historial.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = TipoCambioHistorial::query();

            if ($request->filled('moneda_origen') && $request->filled('moneda_destino')) {
                $query->porMonedas($request->moneda_origen, $request->moneda_destino);
            }

            if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
                $query->porRangoFechas($request->fecha_inicio, $request->fecha_fin);
            }

            $historial = $query->orderBy('fecha', 'desc')->paginate($perPage);

            return response()->json($historial);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/tipos-cambio-historial',
        summary: 'Registrar tipo de cambio',
        description: 'Registra un tipo de cambio histórico',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fecha', 'moneda_origen', 'moneda_destino', 'tasa_compra', 'tasa_venta'],
                properties: [
                    new OA\Property(property: 'fecha', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'moneda_origen', type: 'string', example: 'USD'),
                    new OA\Property(property: 'moneda_destino', type: 'string', example: 'CRC'),
                    new OA\Property(property: 'tasa_compra', type: 'number', format: 'float', example: 520.50),
                    new OA\Property(property: 'tasa_venta', type: 'number', format: 'float', example: 530.75),
                    new OA\Property(property: 'fuente', type: 'string', example: 'BCCR'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tipo de cambio registrado exitosamente',
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
        $this->authorize('create', TipoCambioHistorial::class);

        $validated = $request->validate([
            'fecha' => 'required|date',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'tasa_compra' => 'required|numeric|min:0',
            'tasa_venta' => 'required|numeric|min:0',
            'fuente' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Verificar que no exista ya un registro para la misma fecha y monedas
            $existe = TipoCambioHistorial::where('fecha', $validated['fecha'])
                ->where('moneda_origen', $validated['moneda_origen'])
                ->where('moneda_destino', $validated['moneda_destino'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => 'Ya existe un tipo de cambio para esta fecha y monedas'
                ], 422);
            }

            $tipoCambio = TipoCambioHistorial::create($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Tipo de cambio registrado exitosamente',
                'data' => $tipoCambio
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar tipo de cambio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/tipos-cambio-historial/{id}',
        summary: 'Obtener tipo de cambio específico',
        description: 'Obtiene un registro específico del historial',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del registro',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Registro obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id)
    {
        $tipoCambio = TipoCambioHistorial::findOrFail($id);
        $this->authorize('view', $tipoCambio);

        $cacheKey = $this->generateCacheKey("tipos_cambio_historial.show.{$id}");

        return $this->getCached($cacheKey, function () use ($tipoCambio) {
            return response()->json(['data' => $tipoCambio]);
        });
    }

    /**
     * Obtener tipo de cambio por fecha específica
     */
    #[OA\Get(
        path: '/api/tipos-cambio-historial/por-fecha',
        summary: 'Obtener tipo de cambio por fecha',
        description: 'Obtiene el tipo de cambio para una fecha y monedas específicas',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        parameters: [
            new OA\Parameter(
                name: 'fecha',
                description: 'Fecha a consultar',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'moneda_origen',
                description: 'Moneda origen',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'USD')
            ),
            new OA\Parameter(
                name: 'moneda_destino',
                description: 'Moneda destino',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'CRC')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tipo de cambio obtenido exitosamente'
            )
        ]
    )]
    public function porFecha(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
        ]);

        $tipoCambio = TipoCambioHistorial::where('fecha', $validated['fecha'])
            ->where('moneda_origen', $validated['moneda_origen'])
            ->where('moneda_destino', $validated['moneda_destino'])
            ->first();

        if (!$tipoCambio) {
            return response()->json([
                'message' => 'No se encontró tipo de cambio para la fecha especificada'
            ], 404);
        }

        return response()->json(['data' => $tipoCambio]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/tipos-cambio-historial/{id}',
        summary: 'Actualizar tipo de cambio',
        description: 'Actualiza las tasas de un tipo de cambio existente',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del registro',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tasa_compra', type: 'number', format: 'float'),
                    new OA\Property(property: 'tasa_venta', type: 'number', format: 'float'),
                    new OA\Property(property: 'fuente', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tipo de cambio actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $tipoCambio = TipoCambioHistorial::findOrFail($id);
        $this->authorize('update', $tipoCambio);

        $validated = $request->validate([
            'tasa_compra' => 'sometimes|numeric|min:0',
            'tasa_venta' => 'sometimes|numeric|min:0',
            'fuente' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $tipoCambio->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Tipo de cambio actualizado exitosamente',
                'data' => $tipoCambio->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar tipo de cambio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/tipos-cambio-historial/{id}',
        summary: 'Eliminar tipo de cambio',
        description: 'Elimina un registro del historial',
        security: [['sanctum' => []]],
        tags: ['Tipos Cambio Historial'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del registro',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tipo de cambio eliminado exitosamente'
            )
        ]
    )]
    public function destroy(string $id)
    {
        $tipoCambio = TipoCambioHistorial::findOrFail($id);
        $this->authorize('delete', $tipoCambio);

        DB::beginTransaction();
        try {
            $tipoCambio->delete();

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Tipo de cambio eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar tipo de cambio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
