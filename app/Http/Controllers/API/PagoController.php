<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Pagos
 *
 * Gestiona los pagos de cuentas por cobrar y por pagar.
 * Actualiza automáticamente los saldos de las cuentas relacionadas.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PagoController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /**
     * Tags para invalidación de cache
     * @var array<string>
     */
    protected array $cacheTags = ['pagos', 'finanzas'];

    /**
     * TTL del cache en segundos (30 minutos)
     * Datos dinámicos: pagos se registran frecuentemente
     * @var int
     */
    protected int $cacheTTL = 1800;
    /**
     * Listar todos los pagos de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/pagos",
        summary: "Listar todos los pagos",
        description: "Obtiene un listado de todos los pagos registrados en el sistema. Permite filtrar por estado, forma de pago, proveedor, cliente y rango de fechas. Actualiza automáticamente los saldos de cuentas por cobrar y por pagar.",
        security: [["sanctum" => []]],
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado del pago",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Pendiente", "Pagado", "Cancelado"])
            ),
            new OA\Parameter(
                name: "forma_pago_id",
                in: "query",
                description: "Filtrar por forma de pago",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "proveedor_id",
                in: "query",
                description: "Filtrar por proveedor",
                required: false,
                schema: new OA\Schema(type: "integer", example: 5)
            ),
            new OA\Parameter(
                name: "cliente_id",
                in: "query",
                description: "Filtrar por cliente",
                required: false,
                schema: new OA\Schema(type: "integer", example: 12)
            ),
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha de inicio del rango (formato: Y-m-d)",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha fin del rango (formato: Y-m-d)",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de pagos obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Pago")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Pago::class);
        
        $empresaId = $this->getEmpresaId();
        
        $cacheKey = $this->getCacheKey('index', [
            'empresa_id' => $empresaId,
            'estado' => $request->estado,
            'forma_pago_id' => $request->forma_pago_id,
            'proveedor_id' => $request->proveedor_id,
            'cliente_id' => $request->cliente_id,
            'desde' => $request->desde,
            'hasta' => $request->hasta,
            'per_page' => $request->per_page
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function () use ($request, $empresaId) {
            $query = Pago::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra']);

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por forma de pago
            if ($request->filled('forma_pago_id')) {
                $query->where('forma_pago_id', $request->forma_pago_id);
            }

            // Filtro por proveedor
            if ($request->filled('proveedor_id')) {
                $query->where('proveedor_id', $request->proveedor_id);
            }

            // Filtro por cliente
            if ($request->filled('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            // Filtro por rango de fechas
            if ($request->filled('desde') && $request->filled('hasta')) {
                $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
            }

            // Ordenamiento
            $query->orderBy($request->get('sort_by', 'fecha_pago'), $request->get('sort_order', 'desc'));

            $pagos = $query->paginate($request->get('per_page', 15));

            return PagoResource::collection($pagos);
        });
    }

    /**
     * Crear un nuevo pago
     *
     * @param StorePagoRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/pagos",
        summary: "Crear un nuevo pago",
        description: "Registra un nuevo pago en el sistema. Actualiza automáticamente el saldo de la cuenta por cobrar o cuenta por pagar relacionada. Utiliza transacciones para garantizar la integridad de los datos.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["fecha_pago", "monto", "estado"],
                properties: [
                    new OA\Property(property: "orden_compra_id", type: "integer", example: 15),
                    new OA\Property(property: "cuenta_por_pagar_id", type: "integer", example: 8),
                    new OA\Property(property: "proveedor_id", type: "integer", example: 5),
                    new OA\Property(property: "cliente_id", type: "integer", example: 12),
                    new OA\Property(property: "cuenta_por_cobrar_id", type: "integer", example: 20),
                    new OA\Property(property: "forma_pago_id", type: "integer", example: 2),
                    new OA\Property(property: "fecha_pago", type: "string", format: "date", example: "2024-01-15"),
                    new OA\Property(property: "monto", type: "number", format: "decimal", example: 15000.50),
                    new OA\Property(property: "moneda", type: "string", maxLength: 3, example: "CRC"),
                    new OA\Property(property: "descripcion", type: "string", example: "Pago parcial de factura #1234"),
                    new OA\Property(property: "referencia", type: "string", example: "REF-2024-001"),
                    new OA\Property(property: "estado", type: "string", enum: ["Pendiente", "Pagado", "Cancelado"], example: "Pagado")
                ]
            )
        ),
        tags: ["Pagos"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Pago creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Pago")
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function store(StorePagoRequest $request): JsonResponse
    {
        $this->authorize('create', Pago::class);
        
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['empresa_id'] = $this->getEmpresaId();

            $pago = Pago::create($validated);

            // Actualizar saldo de cuenta por pagar si aplica
            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, $pago->monto);
            }

            // Actualizar saldo de cuenta por cobrar si aplica
            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, $pago->monto);
            }

            $pago->load(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']);

            DB::commit();

            return (new PagoResource($pago))
                ->additional(['message' => 'Pago registrado exitosamente'])
                ->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un pago específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/pagos/{id}",
        summary: "Obtener un pago específico",
        description: "Obtiene los detalles completos de un pago específico, incluyendo relaciones con forma de pago, proveedor, cliente, cuenta por pagar, cuenta por cobrar y orden de compra.",
        security: [["sanctum" => []]],
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago encontrado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/Pago")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function show(int $id, Request $request): PagoResource
    {
        $empresaId = $this->getEmpresaId();

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra'])
            ->firstOrFail();
        
        $this->authorize('view', $pago);

        return new PagoResource($pago);
    }

    /**
     * Actualizar un pago existente
     *
     * @param UpdatePagoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: "/api/pagos/{id}",
        summary: "Actualizar un pago existente",
        description: "Actualiza los datos de un pago existente. No permite modificar pagos en estado 'Pagado'. Actualiza automáticamente los saldos de cuentas si cambia el monto.",
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "orden_compra_id", type: "integer", example: 15),
                    new OA\Property(property: "cuenta_por_pagar_id", type: "integer", example: 8),
                    new OA\Property(property: "proveedor_id", type: "integer", example: 5),
                    new OA\Property(property: "cliente_id", type: "integer", example: 12),
                    new OA\Property(property: "cuenta_por_cobrar_id", type: "integer", example: 20),
                    new OA\Property(property: "forma_pago_id", type: "integer", example: 2),
                    new OA\Property(property: "fecha_pago", type: "string", format: "date", example: "2024-01-15"),
                    new OA\Property(property: "monto", type: "number", format: "decimal", example: 15000.50),
                    new OA\Property(property: "moneda", type: "string", maxLength: 3, example: "CRC"),
                    new OA\Property(property: "descripcion", type: "string", example: "Pago parcial de factura #1234"),
                    new OA\Property(property: "referencia", type: "string", example: "REF-2024-001"),
                    new OA\Property(property: "estado", type: "string", enum: ["Pendiente", "Pagado", "Cancelado"], example: "Pagado")
                ]
            )
        ),
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago a actualizar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago actualizado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Pago actualizado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Pago")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "Error de validación o pago en estado 'Pagado'"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function update(UpdatePagoRequest $request, int $id): PagoResource
    {
        $empresaId = $this->getEmpresaId();

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        
        $this->authorize('update', $pago);

        // No permitir modificar pagos ya procesados
        if ($pago->estado === 'Pagado') {
            abort(422, 'No se puede modificar un pago que ya ha sido procesado');
        }

        try {
            DB::beginTransaction();

            $montoAnterior = $pago->monto;
            $pago->update($request->validated());

            // Si cambió el monto, ajustar los saldos
            if ($request->filled('monto') && $request->monto != $montoAnterior) {
                $diferencia = $request->monto - $montoAnterior;

                if ($pago->cuenta_por_pagar_id) {
                    $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, $diferencia);
                }

                if ($pago->cuenta_por_cobrar_id) {
                    $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, $diferencia);
                }
            }

            $pago->load(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']);

            DB::commit();

            return (new PagoResource($pago))
                ->additional(['message' => 'Pago actualizado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }* Eliminar (soft delete) un pago
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/pagos/{id}",
        summary: "Eliminar un pago",
        description: "Realiza un soft delete del pago especificado. No permite eliminar pagos en estado 'Pagado'. Revierte los saldos de las cuentas relacionadas al eliminar.",
        security: [["sanctum" => []]],
        tags: ["Pagos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago a eliminar",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Pago eliminado exitosamente"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Pago")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Pago no encontrado"
            ),
            new OA\Response(
                response: 422,
                description: "No se puede eliminar un pago en estado 'Pagado'"
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();
        
        $this->authorize('delete', $pago);

        // No permitir eliminar pagos ya procesados
        if ($pago->estado === 'Pagado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un pago que ya ha sido procesado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Revertir el monto en las cuentas
            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, -$pago->monto);
            }

            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, -$pago->monto);
            }

            $pago->update(['eliminado' => 1, 'activo' => 0]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el monto pagado en una cuenta por pagar
     *
     * @param int $cuentaId
     * @param float $monto
     * @return void
     */
    private function actualizarCuentaPorPagar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorPagar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        // Actualizar estado según saldo
        if ($cuenta->monto_pagado >= $cuenta->monto_original) {
            $cuenta->update(['estado' => 'Pagada Totalmente']);
        } elseif ($cuenta->monto_pagado > 0) {
            $cuenta->update(['estado' => 'Pagada Parcialmente']);
        }
    }

    /**
     * Actualizar el monto pagado en una cuenta por cobrar
     *
     * @param int $cuentaId
     * @param float $monto
     * @return void
     */
    private function actualizarCuentaPorCobrar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorCobrar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        // Actualizar estado según saldo
        if ($cuenta->monto_pagado >= $cuenta->monto_original) {
            $cuenta->update(['estado' => 'Pagada Totalmente']);
        } elseif ($cuenta->monto_pagado > 0) {
            $cuenta->update(['estado' => 'Pagada Parcialmente']);
        }
    }

    /**
     * Obtener resumen de pagos por forma de pago
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/pagos/resumen-por-forma-pago",
        summary: "Resumen de pagos por forma de pago",
        description: "Obtiene un resumen estadístico de todos los pagos agrupados por forma de pago. Incluye cantidad de pagos y total pagado por cada forma de pago. Solo considera pagos en estado 'Pagado'.",
        security: [["sanctum" => []]],
        tags: ["Pagos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen generado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "forma_pago_id", type: "integer", example: 1),
                                    new OA\Property(property: "cantidad", type: "integer", example: 25),
                                    new OA\Property(property: "total", type: "number", format: "decimal", example: 450000.00)
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error interno del servidor"
            )
        ]
    )]
    public function resumenPorFormaPago(Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $resumen = Pago::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('estado', 'Pagado')
            ->select('forma_pago_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto) as total'))
            ->groupBy('forma_pago_id')
            ->with('formaPago')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
