<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PagoCuentaCobrar;
use App\Models\CuentaPorCobrar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCacheableQueries;
use OpenApi\Attributes as OA;

class PagoCuentaCobrarController extends Controller
{
    use HasCacheableQueries;
    
    protected $cacheTags = ['pagos_cuentas_cobrar', 'cuentas_cobrar', 'transacciones'];
    protected $cacheTTL = 600; // 10 minutos

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    #[OA\Get(
        path: '/api/pagos-cuentas-cobrar',
        summary: 'Listar pagos de cuentas por cobrar',
        description: 'Obtiene un listado paginado de pagos con filtros',
        security: [['sanctum' => []]],
        tags: ['Pagos Cuentas Cobrar'],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 15)
            ),
            new OA\Parameter(
                name: 'cuenta_por_cobrar_id',
                description: 'Filtrar por cuenta por cobrar',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'forma_pago_id',
                description: 'Filtrar por forma de pago',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
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
        $this->authorize('viewAny', PagoCuentaCobrar::class);

        $cacheKey = $this->generateCacheKey('pagos_cuentas_cobrar.index', $request->all());

        return $this->getCached($cacheKey, function () use ($request) {
            $perPage = $request->input('per_page', 15);
            
            $query = PagoCuentaCobrar::with(['cuentaPorCobrar', 'formaPago'])
                ->activos();

            if ($request->filled('cuenta_por_cobrar_id')) {
                $query->where('cuenta_por_cobrar_id', $request->cuenta_por_cobrar_id);
            }

            if ($request->filled('forma_pago_id')) {
                $query->where('forma_pago_id', $request->forma_pago_id);
            }

            if ($request->filled('fecha_desde') && $request->filled('fecha_hasta')) {
                $query->fechaBetween($request->fecha_desde, $request->fecha_hasta);
            }

            $pagos = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json($pagos);
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: '/api/pagos-cuentas-cobrar',
        summary: 'Registrar pago de cuenta por cobrar',
        description: 'Registra un nuevo pago y actualiza el saldo de la cuenta',
        security: [['sanctum' => []]],
        tags: ['Pagos Cuentas Cobrar'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cuenta_por_cobrar_id', 'forma_pago_id', 'fecha_pago', 'monto_pago'],
                properties: [
                    new OA\Property(property: 'cuenta_por_cobrar_id', type: 'integer', example: 1),
                    new OA\Property(property: 'forma_pago_id', type: 'integer', example: 2),
                    new OA\Property(property: 'fecha_pago', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'monto_pago', type: 'number', format: 'decimal', example: 5000.00),
                    new OA\Property(property: 'numero_referencia', type: 'string', example: 'REF-12345'),
                    new OA\Property(property: 'moneda', type: 'string', example: 'CRC'),
                    new OA\Property(property: 'observaciones', type: 'string', example: 'Pago parcial'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pago registrado exitosamente',
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
    public function store(Request $request)
    {
        $this->authorize('create', PagoCuentaCobrar::class);

        $validated = $request->validate([
            'cuenta_por_cobrar_id' => 'required|exists:cuentas_por_cobrar,id',
            'forma_pago_id' => 'required|exists:formas_pago,id',
            'fecha_pago' => 'required|date',
            'monto_pago' => 'required|numeric|min:0.01',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|size:3',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Verificar que el monto no exceda el saldo pendiente
            $cuenta = CuentaPorCobrar::findOrFail($validated['cuenta_por_cobrar_id']);
            $saldoPendiente = $cuenta->monto_original - $cuenta->monto_pagado;

            if ($validated['monto_pago'] > $saldoPendiente) {
                return response()->json([
                    'message' => 'El monto del pago excede el saldo pendiente',
                    'saldo_pendiente' => $saldoPendiente
                ], 422);
            }

            // Crear el pago
            $pago = PagoCuentaCobrar::create($validated);
            
            // Actualizar monto pagado en la cuenta
            $cuenta->increment('monto_pagado', $validated['monto_pago']);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Pago registrado exitosamente',
                'data' => $pago->load(['cuentaPorCobrar', 'formaPago'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: '/api/pagos-cuentas-cobrar/{id}',
        summary: 'Obtener pago específico',
        description: 'Obtiene los detalles de un pago',
        security: [['sanctum' => []]],
        tags: ['Pagos Cuentas Cobrar'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del pago',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pago obtenido exitosamente',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'object')])
            )
        ]
    )]
    public function show(string $id)
    {
        $pago = PagoCuentaCobrar::with(['cuentaPorCobrar', 'formaPago'])->findOrFail($id);
        $this->authorize('view', $pago);

        $cacheKey = $this->generateCacheKey("pagos_cuentas_cobrar.show.{$id}");

        return $this->getCached($cacheKey, function () use ($pago) {
            return response()->json(['data' => $pago]);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: '/api/pagos-cuentas-cobrar/{id}',
        summary: 'Actualizar pago',
        description: 'Actualiza la información de un pago existente',
        security: [['sanctum' => []]],
        tags: ['Pagos Cuentas Cobrar'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del pago',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'numero_referencia', type: 'string'),
                    new OA\Property(property: 'observaciones', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pago actualizado exitosamente'
            )
        ]
    )]
    public function update(Request $request, string $id)
    {
        $pago = PagoCuentaCobrar::findOrFail($id);
        $this->authorize('update', $pago);

        $validated = $request->validate([
            'numero_referencia' => 'sometimes|string|max:100',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pago->update($validated);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Pago actualizado exitosamente',
                'data' => $pago->fresh(['cuentaPorCobrar', 'formaPago'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al actualizar pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: '/api/pagos-cuentas-cobrar/{id}',
        summary: 'Anular pago',
        description: 'Anula un pago y revierte el monto en la cuenta',
        security: [['sanctum' => []]],
        tags: ['Pagos Cuentas Cobrar'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID del pago',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pago anulado exitosamente'
            )
        ]
    )]
    public function destroy(string $id)
    {
        $pago = PagoCuentaCobrar::findOrFail($id);
        $this->authorize('delete', $pago);

        DB::beginTransaction();
        try {
            // Revertir monto pagado en la cuenta
            $cuenta = CuentaPorCobrar::findOrFail($pago->cuenta_por_cobrar_id);
            $cuenta->decrement('monto_pagado', $pago->monto_pago);
            
            // Soft delete del pago
            $pago->update([
                'eliminado' => true,
                'activo' => false
            ]);

            DB::commit();
            $this->clearCache();

            return response()->json([
                'message' => 'Pago anulado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al anular pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
