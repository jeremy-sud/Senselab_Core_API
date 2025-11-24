<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoNominaRequest;
use App\Http\Requests\UpdatePagoNominaRequest;
use App\Http\Resources\PagoNominaResource;
use App\Models\PagoNomina;
use App\Traits\HasCacheableQueries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Pagos de Nómina
 *
 * Gestiona los pagos individuales de nómina por empleado y período.
 * Incluye cálculo de monto bruto, deducciones y monto neto.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PagoNominaController extends Controller
{
    use HasCacheableQueries;

    protected array $cacheTags = ['pagos-nomina', 'nomina', 'rrhh'];
    protected int $cacheTTL = 1200; // 20min - payroll payments, dynamic
    /**
     * Listar todos los pagos de nómina de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/pagos-nomina",
        summary: "Listar pagos de nómina",
        description: "Obtiene listado paginado de pagos de nómina con filtros por empleado, período, estado y rango de fechas.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "empleado_id",
                in: "query",
                description: "Filtrar por ID de empleado",
                required: false,
                schema: new OA\Schema(type: "integer", example: 5)
            ),
            new OA\Parameter(
                name: "periodo_nomina_id",
                in: "query",
                description: "Filtrar por ID de período de nómina",
                required: false,
                schema: new OA\Schema(type: "integer", example: 12)
            ),
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["pendiente", "pagado", "cancelado"], example: "pagado")
            ),
            new OA\Parameter(
                name: "desde",
                in: "query",
                description: "Fecha de inicio del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-01-01")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                description: "Fecha de fin del rango",
                required: false,
                schema: new OA\Schema(type: "string", format: "date", example: "2024-12-31")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PagoNomina::class);
        
        $empresaId = $request->user()->empresa_id;
        
        $cacheKey = $this->getCacheKey('index', [
            'empleado_id' => $request->empleado_id,
            'periodo_nomina_id' => $request->periodo_nomina_id,
            'estado' => $request->estado,
            'desde' => $request->desde,
            'hasta' => $request->hasta
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = PagoNomina::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['empresa', 'empleado', 'periodoNomina', 'metodoPago']);

            // Filtro por empleado
            if ($request->filled('empleado_id')) {
                $query->where('empleado_id', $request->empleado_id);
            }

            // Filtro por período
            if ($request->filled('periodo_nomina_id')) {
                $query->where('periodo_nomina_id', $request->periodo_nomina_id);
            }

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por rango de fechas
            if ($request->filled('desde') && $request->filled('hasta')) {
                $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
            }

            $pagos = $query->orderBy('fecha_pago', 'desc')->paginate(15);

            return PagoNominaResource::collection($pagos);
        });
    }

    /**
     * Crear un nuevo pago de nómina
     *
     * @param StorePagoNominaRequest $request
     * @return PagoNominaResource
     */
    #[OA\Post(
        path: "/api/pagos-nomina",
        summary: "Crear pago de nómina",
        description: "Registra un nuevo pago de nómina con cálculo de monto bruto, deducciones y neto.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["empleado_id", "periodo_nomina_id", "fecha_pago", "monto_bruto", "total_deducciones", "monto_neto_pagado"],
                properties: [
                    new OA\Property(property: "empleado_id", type: "integer", example: 5),
                    new OA\Property(property: "periodo_nomina_id", type: "integer", example: 12),
                    new OA\Property(property: "fecha_pago", type: "string", format: "date-time", example: "2024-01-15 10:00:00"),
                    new OA\Property(property: "monto_bruto", type: "number", format: "decimal", example: 500000.00),
                    new OA\Property(property: "total_deducciones", type: "number", format: "decimal", example: 45000.00),
                    new OA\Property(property: "monto_neto_pagado", type: "number", format: "decimal", example: 455000.00),
                    new OA\Property(property: "metodo_pago_id", type: "integer", example: 1),
                    new OA\Property(property: "referencia_pago", type: "string", maxLength: 100, example: "TRANS-12345", nullable: true),
                    new OA\Property(property: "estado", type: "string", enum: ["pendiente", "pagado", "cancelado"], example: "pendiente"),
                    new OA\Property(property: "observaciones", type: "string", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Pago creado exitosamente"),
            new OA\Response(response: 422, description: "Error de validación"),
            new OA\Response(response: 500, description: "Error del servidor")
        ]
    )]
    public function store(StorePagoNominaRequest $request): PagoNominaResource
    {
        $this->authorize('create', PagoNomina::class);
        
        $empresaId = $request->user()->empresa_id;

        DB::beginTransaction();

        try {
            $pago = PagoNomina::create([
                'empresa_id' => $empresaId,
                'empleado_id' => $request->empleado_id,
                'periodo_nomina_id' => $request->periodo_nomina_id,
                'fecha_pago' => $request->fecha_pago,
                'monto_bruto' => $request->monto_bruto,
                'total_deducciones' => $request->total_deducciones,
                'monto_neto_pagado' => $request->monto_neto_pagado,
                'metodo_pago_id' => $request->metodo_pago_id,
                'referencia_pago' => $request->referencia_pago,
                'estado' => $request->estado ?? 'pendiente',
                'observaciones' => $request->observaciones,
                'activo' => $request->activo ?? 1
            ]);

            DB::commit();
            
            $this->flushCache();

            return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al crear el pago de nómina: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar un pago de nómina específico
     *
     * @param int $id
     * @param Request $request
     * @return PagoNominaResource
     */
    #[OA\Get(
        path: "/api/pagos-nomina/{id}",
        summary: "Obtener pago de nómina",
        description: "Obtiene los detalles completos de un pago de nómina incluyendo empleado, período y método de pago.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Pago encontrado"),
            new OA\Response(response: 404, description: "Pago no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id, Request $request): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'empleado', 'periodoNomina', 'metodoPago'])
            ->findOrFail($id);
        
        $this->authorize('view', $pago);

        return new PagoNominaResource($pago);
    }

    /**
     * Actualizar un pago de nómina
     *
     * @param UpdatePagoNominaRequest $request
     * @param int $id
     * @return PagoNominaResource
     */
    #[OA\Put(
        path: "/api/pagos-nomina/{id}",
        summary: "Actualizar pago de nómina",
        description: "Actualiza un pago de nómina. No permite modificar pagos ya marcados como pagados.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "empleado_id", type: "integer", example: 5),
                    new OA\Property(property: "periodo_nomina_id", type: "integer", example: 12),
                    new OA\Property(property: "fecha_pago", type: "string", format: "date-time", example: "2024-01-15 10:00:00"),
                    new OA\Property(property: "monto_bruto", type: "number", format: "decimal", example: 500000.00),
                    new OA\Property(property: "total_deducciones", type: "number", format: "decimal", example: 45000.00),
                    new OA\Property(property: "monto_neto_pagado", type: "number", format: "decimal", example: 455000.00),
                    new OA\Property(property: "metodo_pago_id", type: "integer", example: 1),
                    new OA\Property(property: "referencia_pago", type: "string", maxLength: 100, example: "TRANS-12345", nullable: true),
                    new OA\Property(property: "estado", type: "string", enum: ["pendiente", "pagado", "cancelado"]),
                    new OA\Property(property: "observaciones", type: "string", nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Pago actualizado exitosamente"),
            new OA\Response(response: 404, description: "Pago no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un pago ya pagado"),
            new OA\Response(response: 500, description: "Error del servidor")
        ]
    )]
    public function update(UpdatePagoNominaRequest $request, int $id): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('update', $pago);

        // Validar que no esté pagado
        if ($pago->estado === 'pagado') {
            abort(422, 'No se puede modificar un pago de nómina que ya ha sido marcado como pagado');
        }

        DB::beginTransaction();

        try {
            $pago->update($request->only([
                'empleado_id',
                'periodo_nomina_id',
                'fecha_pago',
                'monto_bruto',
                'total_deducciones',
                'monto_neto_pagado',
                'metodo_pago_id',
                'referencia_pago',
                'estado',
                'observaciones',
                'activo'
            ]));

            DB::commit();
            
            $this->flushCache();

            return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));

        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, 'Error al actualizar el pago de nómina: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar (soft delete) un pago de nómina
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/pagos-nomina/{id}",
        summary: "Eliminar pago de nómina",
        description: "Realiza soft delete de un pago de nómina. No permite eliminar pagos ya pagados.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago de nómina",
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
                        new OA\Property(property: "message", type: "string", example: "Pago de nómina eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Pago no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un pago ya pagado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('delete', $pago);

        // Validar que no esté pagado
        if ($pago->estado === 'pagado') {
            return response()->json([
                'message' => 'No se puede eliminar un pago de nómina que ya ha sido pagado'
            ], 422);
        }

        $pago->update(['eliminado' => 1, 'activo' => 0]);
        
        $this->flushCache();

        return response()->json([
            'message' => 'Pago de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Marcar un pago como pagado
     *
     * @param int $id
     * @param Request $request
     * @return PagoNominaResource
     */
    #[OA\Post(
        path: "/api/pagos-nomina/{id}/marcar-pagado",
        summary: "Marcar pago como pagado",
        description: "Cambia el estado de un pago de nómina a 'pagado'. Registra la fecha de pago.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del pago de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "fecha_pago", type: "string", format: "date-time", example: "2024-01-15 14:30:00", description: "Fecha de pago (opcional, usa fecha actual si se omite)")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Pago marcado como pagado exitosamente"),
            new OA\Response(response: 404, description: "Pago no encontrado"),
            new OA\Response(response: 422, description: "El pago ya ha sido marcado como pagado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function marcarPagado(int $id, Request $request): PagoNominaResource
    {
        $empresaId = $request->user()->empresa_id;

        $pago = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($pago->estado === 'pagado') {
            abort(422, 'Este pago ya ha sido marcado como pagado');
        }

        $pago->update([
            'estado' => 'pagado',
            'fecha_pago' => $request->fecha_pago ?? now()
        ]);

        return new PagoNominaResource($pago->load(['empresa', 'empleado', 'periodoNomina', 'metodoPago']));
    }

    /**
     * Obtener pagos por empleado
     *
     * @param int $empleadoId
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/pagos-nomina/empleado/{empleadoId}",
        summary: "Pagos por empleado",
        description: "Obtiene el historial de pagos de nómina de un empleado específico.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "empleadoId",
                in: "path",
                description: "ID del empleado",
                required: true,
                schema: new OA\Schema(type: "integer", example: 5)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Historial de pagos obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function porEmpleado(int $empleadoId, Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;

        $pagos = PagoNomina::where('empresa_id', $empresaId)
            ->where('empleado_id', $empleadoId)
            ->where('eliminado', 0)
            ->with(['periodoNomina', 'metodoPago'])
            ->orderBy('fecha_pago', 'desc')
            ->paginate(15);

        return PagoNominaResource::collection($pagos);
    }

    /**
     * Obtener resumen de pagos por método de pago
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/pagos-nomina/resumen-metodo-pago",
        summary: "Resumen por método de pago",
        description: "Obtiene estadísticas agregadas de pagos agrupados por método de pago (efectivo, transferencia, cheque, etc.). Incluye total de pagos y monto total.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "periodo_nomina_id",
                in: "query",
                description: "Filtrar por período de nómina",
                required: false,
                schema: new OA\Schema(type: "integer", example: 12)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen obtenido exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "metodo_pago_id", type: "integer", example: 3),
                            new OA\Property(property: "metodo_pago", type: "string", example: "Transferencia Bancaria"),
                            new OA\Property(property: "total_pagos", type: "integer", example: 38),
                            new OA\Property(property: "total_monto", type: "number", format: "decimal", example: 18950000.00)
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function resumenPorMetodoPago(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0);

        // Filtro opcional por período
        if ($request->filled('periodo_nomina_id')) {
            $query->where('periodo_nomina_id', $request->periodo_nomina_id);
        }

        $resumen = $query->join('formas_pago', 'pagos_nomina.metodo_pago_id', '=', 'formas_pago.id')
            ->selectRaw('
                formas_pago.id,
                formas_pago.nombre as metodo_pago,
                COUNT(pagos_nomina.id) as total_pagos,
                SUM(pagos_nomina.monto_neto_pagado) as total_monto
            ')
            ->groupBy('formas_pago.id', 'formas_pago.nombre')
            ->get();

        return response()->json($resumen);
    }

    /**
     * Calcular totales de nómina por período
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/pagos-nomina/totales-por-periodo",
        summary: "Totales por período",
        description: "Calcula totales agregados de nómina agrupados por período. Incluye total de empleados, monto bruto, deducciones y neto.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "anio",
                in: "query",
                description: "Filtrar por año",
                required: false,
                schema: new OA\Schema(type: "integer", example: 2024)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Totales calculados exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "periodo_id", type: "integer", example: 12),
                            new OA\Property(property: "nombre_periodo", type: "string", example: "Enero 2024"),
                            new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2024-01-01"),
                            new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2024-01-31"),
                            new OA\Property(property: "total_empleados", type: "integer", example: 45),
                            new OA\Property(property: "total_bruto", type: "number", format: "decimal", example: 22500000.00),
                            new OA\Property(property: "total_deducciones", type: "number", format: "decimal", example: 2025000.00),
                            new OA\Property(property: "total_neto", type: "number", format: "decimal", example: 20475000.00)
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function totalesPorPeriodo(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = PagoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0);

        // Filtro opcional por año
        if ($request->filled('anio')) {
            $query->whereYear('fecha_pago', $request->anio);
        }

        $totales = $query->join('periodos_nomina', 'pagos_nomina.periodo_nomina_id', '=', 'periodos_nomina.id')
            ->selectRaw('
                periodos_nomina.id as periodo_id,
                periodos_nomina.nombre_periodo,
                periodos_nomina.fecha_inicio,
                periodos_nomina.fecha_fin,
                COUNT(pagos_nomina.id) as total_empleados,
                SUM(pagos_nomina.monto_bruto) as total_bruto,
                SUM(pagos_nomina.total_deducciones) as total_deducciones,
                SUM(pagos_nomina.monto_neto_pagado) as total_neto
            ')
            ->groupBy('periodos_nomina.id', 'periodos_nomina.nombre_periodo', 'periodos_nomina.fecha_inicio', 'periodos_nomina.fecha_fin')
            ->orderBy('periodos_nomina.fecha_inicio', 'desc')
            ->get();

        return response()->json($totales);
    }
}
