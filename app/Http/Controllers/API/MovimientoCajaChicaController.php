<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MovimientoCajaChica;
use App\Models\CajaChica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class MovimientoCajaChicaController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['movimientos_caja_chica', 'caja_chica', 'tesoreria'];
    protected $cacheTTL = 600; // 10 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/movimientos-caja-chica',
        summary: 'Listar movimientos de caja chica',
        description: 'Obtiene un listado paginado de movimientos',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'caja_chica_id',
                description: 'Filtrar por fondo de caja chica',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'tipo_movimiento',
                description: 'Filtrar por tipo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['Ingreso', 'Egreso', 'Reembolso', 'Ajuste'])
            ),
            new OA\Parameter(
                name: 'fecha_desde',
                description: 'Filtrar desde fecha',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'fecha_hasta',
                description: 'Filtrar hasta fecha',
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
        $this->authorize('viewAny', MovimientoCajaChica::class);

        $cacheKey = $this->generateCacheKey('movimientos_caja_chica.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = MovimientoCajaChica::with(['cajaChica', 'cuentaContable'])
                ->activos();

            if ($request->filled('caja_chica_id')) {
                $query->porCaja($request->caja_chica_id);
            }

            if ($request->filled('tipo_movimiento')) {
                $query->porTipo($request->tipo_movimiento);
            }

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->fechaBetween($request->fecha_desde, $request->fecha_hasta);
            }

            $movimientos = $query->orderBy('id', 'desc')->cursorPaginate($perPage);

            return response()->json($movimientos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/movimientos-caja-chica',
        summary: 'Registrar movimiento de caja chica',
        description: 'Registra un nuevo movimiento y actualiza el saldo',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['caja_chica_id', 'fecha_movimiento', 'tipo_movimiento', 'monto', 'concepto'],
                properties: [
                    new OA\Property(property: 'caja_chica_id', type: 'integer', example: 1),
                    new OA\Property(property: 'fecha_movimiento', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'tipo_movimiento', type: 'string', enum: ['Ingreso', 'Egreso', 'Reembolso', 'Ajuste'], example: 'Egreso'),
                    new OA\Property(property: 'monto', type: 'number', format: 'decimal', example: 5000.00),
                    new OA\Property(property: 'numero_comprobante', type: 'string', example: 'COMP-001'),
                    new OA\Property(property: 'concepto', type: 'string', example: 'Compra de útiles de oficina'),
                    new OA\Property(property: 'cuenta_contable_id', type: 'integer', example: 10),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Movimiento registrado exitosamente',
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
        $this->authorize('create', MovimientoCajaChica::class);

        $validated = $request->validate([
            'caja_chica_id' => 'required|exists:caja_chica,id',
            'fecha_movimiento' => 'required|date',
            'tipo_movimiento' => 'required|in:Ingreso,Egreso,Reembolso,Ajuste',
            'monto' => 'required|numeric|min:0.01',
            'numero_comprobante' => 'nullable|string|max:100',
            'concepto' => 'required|string',
            'cuenta_contable_id' => 'nullable|exists:cuentas_contables,id',
        ]);

        DB::beginTransaction();
        try {
            $cajaChica = CajaChica::findOrFail($validated['caja_chica_id']);

            // Verificar que el fondo esté abierto
            if (!$cajaChica->estaAbierta()) {
                return response()->json([
                    'message' => 'Solo se pueden registrar movimientos en fondos abiertos'
                ], 422);
            }

            // Verificar saldo disponible para egresos
            if ($validated['tipo_movimiento'] === MovimientoCajaChica::TIPO_EGRESO) {
                if ($cajaChica->saldo_actual < $validated['monto']) {
                    return response()->json([
                        'message' => 'Saldo insuficiente en caja chica',
                        'saldo_actual' => $cajaChica->saldo_actual
                    ], 422);
                }
            }

            // Crear movimiento
            $movimiento = MovimientoCajaChica::create($validated);
            
            // Actualizar saldo de caja chica
            if ($validated['tipo_movimiento'] === MovimientoCajaChica::TIPO_EGRESO) {
                $cajaChica->decrement('saldo_actual', $validated['monto']);
            } else {
                $cajaChica->increment('saldo_actual', $validated['monto']);
            }

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Movimiento registrado exitosamente',
                'data' => $movimiento->load(['cajaChica', 'cuentaContable']),
                'saldo_actual' => $cajaChica->fresh()->saldo_actual
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Obtener movimiento específico',
        description: 'Obtiene los detalles de un movimiento',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento obtenido exitosamente'
            )
        ]
    )]
    public function show(string $id)
    {
        $movimiento = MovimientoCajaChica::with(['cajaChica', 'cuentaContable'])->findOrFail($id);
        $this->authorize('view', $movimiento);

        $cacheKey = $this->generateCacheKey("movimientos_caja_chica.show.{$id}");

        return $this->getCached($cacheKey, function () use ($movimiento) {
            return response()->json(['data' => $movimiento]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Actualizar movimiento',
        description: 'Actualiza información de un movimiento',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'numero_comprobante', type: 'string'),
                    new OA\Property(property: 'concepto', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $movimiento = MovimientoCajaChica::findOrFail($id);
        $this->authorize('update', $movimiento);

        $validated = $request->validate([
            'numero_comprobante' => 'sometimes|string|max:100',
            'concepto' => 'sometimes|string',
        ]);

        DB::beginTransaction();
        try {
            $movimiento->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Movimiento actualizado exitosamente',
                'data' => $movimiento->fresh(['cajaChica', 'cuentaContable'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/movimientos-caja-chica/{id}',
        summary: 'Anular movimiento',
        description: 'Anula un movimiento y revierte el saldo',
        security: [['sanctum' => []]],
        tags: ['Movimientos Caja Chica'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del movimiento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento anulado exitosamente'
            )
        ]
    )]
    public function destroy(string $id)
    {
        $movimiento = MovimientoCajaChica::findOrFail($id);
        $this->authorize('delete', $movimiento);

        DB::beginTransaction();
        try {
            $cajaChica = CajaChica::findOrFail($movimiento->caja_chica_id);

            // Revertir saldo
            if ($movimiento->tipo_movimiento === MovimientoCajaChica::TIPO_EGRESO) {
                $cajaChica->increment('saldo_actual', $movimiento->monto);
            } else {
                $cajaChica->decrement('saldo_actual', $movimiento->monto);
            }
            
            // Soft delete
            $movimiento->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Movimiento anulado exitosamente',
                'saldo_actual' => $cajaChica->fresh()->saldo_actual
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular movimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
