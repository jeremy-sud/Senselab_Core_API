<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodoNominaRequest;
use App\Http\Requests\UpdatePeriodoNominaRequest;
use App\Http\Resources\PeriodoNominaResource;
use App\Models\PeriodoNomina;
use App\Traits\HasCacheableQueries;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Controlador API para Períodos de Nómina
 *
 * Gestiona los períodos de nómina (quincenales, mensuales, etc.) con estados: Abierto, Cerrado, Procesado.
 * Incluye validación de solapamiento de fechas y cálculos de totales por período.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PeriodoNominaController extends Controller
{
    use HasCacheableQueries, HasEmpresaContext;

    /** @var array<string> */
    protected array $cacheTags = ['periodos-nomina', 'nomina', 'rrhh'];
    protected int $cacheTTL = 1800; // 30min - payroll periods, semi-dynamic
    /**
     * Listar todos los períodos de nómina de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    #[OA\Get(
        path: "/api/periodos-nomina",
        summary: "Listar períodos de nómina",
        description: "Obtiene la lista paginada de períodos de nómina de la empresa. Permite filtrar por estado, año y mes.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "estado",
                in: "query",
                description: "Filtrar por estado del período",
                required: false,
                schema: new OA\Schema(type: "string", enum: ["Abierto", "Cerrado", "Procesado"], example: "Abierto")
            ),
            new OA\Parameter(
                name: "anio",
                in: "query",
                description: "Filtrar por año de inicio del período",
                required: false,
                schema: new OA\Schema(type: "integer", example: 2024)
            ),
            new OA\Parameter(
                name: "mes",
                in: "query",
                description: "Filtrar por mes de inicio del período (1-12)",
                required: false,
                schema: new OA\Schema(type: "integer", minimum: 1, maximum: 12, example: 6)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de períodos obtenida exitosamente",
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
        $this->authorize('viewAny', PeriodoNomina::class);
        
        $empresaId = $this->getEmpresaId();
        
        $cacheKey = $this->getCacheKey('index', [
            'empresa_id' => $empresaId,
            'estado' => $request->estado,
            'anio' => $request->anio,
            'mes' => $request->mes
        ]);
        
        return $this->cacheQueryIfEnabled($cacheKey, function() use ($request, $empresaId) {
            $query = PeriodoNomina::where('empresa_id', $empresaId)
                ->where('eliminado', 0)
                ->with(['empresa', 'pagosNomina']);

            // Filtro por estado
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }

            // Filtro por año
            if ($request->filled('anio')) {
                $query->whereYear('fecha_inicio', $request->anio);
            }

            // Filtro por mes
            if ($request->filled('mes')) {
                $query->whereMonth('fecha_inicio', $request->mes);
            }

            $periodos = $query->orderBy('id', 'desc')->paginate(15);

            return PeriodoNominaResource::collection($periodos);
        });
    }

    /**
     * Crear un nuevo período de nómina
     *
     * @param StorePeriodoNominaRequest $request
     * @return PeriodoNominaResource
     */
    #[OA\Post(
        path: "/api/periodos-nomina",
        summary: "Crear período de nómina",
        description: "Crea un nuevo período de nómina en estado Abierto. Valida que no haya solapamiento de fechas con otros períodos.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre_periodo", "fecha_inicio", "fecha_fin", "fecha_pago_estimada"],
                properties: [
                    new OA\Property(property: "nombre_periodo", type: "string", maxLength: 100, example: "Enero 2024 - Primera Quincena"),
                    new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2024-01-15"),
                    new OA\Property(property: "fecha_pago_estimada", type: "string", format: "date", example: "2024-01-17"),
                    new OA\Property(property: "estado", type: "string", enum: ["Abierto", "Cerrado", "Procesado"], example: "Abierto", description: "Opcional, por defecto 'Abierto'"),
                    new OA\Property(property: "observaciones", type: "string", nullable: true, example: "Período quincenal regular"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1, description: "Opcional, por defecto 1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Período de nómina creado exitosamente"),
            new OA\Response(response: 422, description: "Datos de validación incorrectos o fechas solapadas"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function store(StorePeriodoNominaRequest $request): PeriodoNominaResource
    {
        $this->authorize('create', PeriodoNomina::class);
        
        $empresaId = $this->getEmpresaId();

        $periodo = PeriodoNomina::create([
            'empresa_id' => $empresaId,
            'nombre_periodo' => $request->nombre_periodo,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'fecha_pago_estimada' => $request->fecha_pago_estimada,
            'estado' => $request->estado ?? 'Abierto',
            'observaciones' => $request->observaciones,
            'activo' => $request->activo ?? 1
        ]);
        
        $this->flushCache();

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Mostrar un período de nómina específico
     *
     * @param int $id
     * @param Request $request
     * @return PeriodoNominaResource
     */
    #[OA\Get(
        path: "/api/periodos-nomina/{id}",
        summary: "Obtener período de nómina",
        description: "Obtiene el detalle de un período de nómina específico con sus pagos asociados.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Período obtenido exitosamente"),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function show(int $id, Request $request): PeriodoNominaResource
    {
        $empresaId = $this->getEmpresaId();

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'pagosNomina.empleado', 'pagosNomina.metodoPago'])
            ->findOrFail($id);
        
        $this->authorize('view', $periodo);

        return new PeriodoNominaResource($periodo);
    }

    /**
     * Actualizar un período de nómina
     *
     * @param UpdatePeriodoNominaRequest $request
     * @param int $id
     * @return PeriodoNominaResource
     */
    #[OA\Put(
        path: "/api/periodos-nomina/{id}",
        summary: "Actualizar período de nómina",
        description: "Actualiza un período de nómina existente. No permite modificar períodos en estado 'Procesado'.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nombre_periodo", type: "string", maxLength: 100, example: "Enero 2024 - Primera Quincena"),
                    new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2024-01-01"),
                    new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2024-01-15"),
                    new OA\Property(property: "fecha_pago_estimada", type: "string", format: "date", example: "2024-01-17"),
                    new OA\Property(property: "estado", type: "string", enum: ["Abierto", "Cerrado", "Procesado"], example: "Abierto"),
                    new OA\Property(property: "observaciones", type: "string", nullable: true, example: "Período quincenal regular"),
                    new OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Período actualizado exitosamente"),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 422, description: "No se puede modificar un período procesado o datos inválidos"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function update(UpdatePeriodoNominaRequest $request, int $id): PeriodoNominaResource
    {
        $empresaId = $this->getEmpresaId();

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('update', $periodo);

        // Validar que no esté procesado
        if ($periodo->estado === 'Procesado') {
            abort(422, 'No se puede modificar un período de nómina que ya ha sido procesado');
        }

        $periodo->update($request->only([
            'nombre_periodo',
            'fecha_inicio',
            'fecha_fin',
            'fecha_pago_estimada',
            'estado',
            'observaciones',
            'activo'
        ]));
        
        $this->flushCache();

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Eliminar (soft delete) un período de nómina
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Delete(
        path: "/api/periodos-nomina/{id}",
        summary: "Eliminar período de nómina",
        description: "Elimina lógicamente un período de nómina. No permite eliminar períodos con pagos de nómina asociados.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Período eliminado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Período de nómina eliminado exitosamente")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 422, description: "No se puede eliminar un período con pagos asociados"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $this->getEmpresaId();

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);
        
        $this->authorize('delete', $periodo);

        // Validar que no tenga pagos asociados
        if ($periodo->pagosNomina()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar un período con pagos de nómina asociados'
            ], 422);
        }

        $periodo->update(['eliminado' => 1, 'activo' => 0]);
        
        $this->flushCache();

        return response()->json([
            'message' => 'Período de nómina eliminado exitosamente'
        ]);
    }

    /**
     * Cerrar un período de nómina (cambiar estado a Cerrado)
     *
     * @param int $id
     * @param Request $request
     * @return PeriodoNominaResource
     */
    #[OA\Post(
        path: "/api/periodos-nomina/{id}/cerrar",
        summary: "Cerrar período de nómina",
        description: "Cambia el estado del período de 'Abierto' a 'Cerrado'. Solo permite cerrar períodos en estado 'Abierto'.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Período cerrado exitosamente"),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 422, description: "Solo se pueden cerrar períodos en estado Abierto"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function cerrar(int $id, Request $request): PeriodoNominaResource
    {
        $empresaId = $this->getEmpresaId();

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($periodo->estado !== 'Abierto') {
            abort(422, 'Solo se pueden cerrar períodos en estado Abierto');
        }

        $periodo->update(['estado' => 'Cerrado']);

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * Procesar un período de nómina (cambiar estado a Procesado)
     * Genera los pagos de nómina para todos los empleados activos
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: "/api/periodos-nomina/{id}/procesar",
        summary: "Procesar período de nómina",
        description: "Cambia el estado del período a 'Procesado' y genera los pagos de nómina para todos los empleados activos. Esta acción es irreversible.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Período procesado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Período de nómina procesado exitosamente"),
                        new OA\Property(property: "periodo", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 422, description: "Este período ya ha sido procesado"),
            new OA\Response(response: 500, description: "Error al procesar el período"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function procesar(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        if ($periodo->estado === 'Procesado') {
            return response()->json([
                'message' => 'Este período ya ha sido procesado'
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Aquí se implementaría la lógica para generar los pagos de nómina
            // Por ahora solo cambiamos el estado
            $periodo->update(['estado' => 'Procesado']);

            DB::commit();

            return response()->json([
                'message' => 'Período de nómina procesado exitosamente',
                'periodo' => new PeriodoNominaResource($periodo)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar el período de nómina',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de totales del período
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/periodos-nomina/{id}/resumen",
        summary: "Resumen del período",
        description: "Obtiene estadísticas y totales del período de nómina: total de empleados, monto bruto, deducciones y neto pagado.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "ID del período de nómina",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Resumen obtenido exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "periodo",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "nombre_periodo", type: "string", example: "Enero 2024 - Primera Quincena"),
                                new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2024-01-01"),
                                new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2024-01-15"),
                                new OA\Property(property: "estado", type: "string", example: "Procesado")
                            ],
                            type: "object"
                        ),
                        new OA\Property(
                            property: "resumen",
                            properties: [
                                new OA\Property(property: "total_empleados", type: "integer", example: 45),
                                new OA\Property(property: "total_bruto", type: "string", example: "11250000.00"),
                                new OA\Property(property: "total_deducciones", type: "string", example: "1012500.00"),
                                new OA\Property(property: "total_neto", type: "string", example: "10237500.00")
                            ],
                            type: "object"
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Período no encontrado"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function resumen(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        $totales = $periodo->pagosNomina()
            ->selectRaw('
                COUNT(*) as total_empleados,
                SUM(monto_bruto) as total_bruto,
                SUM(total_deducciones) as total_deducciones,
                SUM(monto_neto_pagado) as total_neto
            ')
            ->first();

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
                'estado' => $periodo->estado
            ],
            'resumen' => [
                'total_empleados' => $totales->total_empleados ?? 0,
                'total_bruto' => number_format($totales->total_bruto ?? 0, 2),
                'total_deducciones' => number_format($totales->total_deducciones ?? 0, 2),
                'total_neto' => number_format($totales->total_neto ?? 0, 2)
            ]
        ]);
    }

    /**
     * Listar períodos activos para selección en formularios
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: "/api/periodos-nomina/activos",
        summary: "Listar períodos activos",
        description: "Obtiene una lista simplificada de períodos de nómina activos para su uso en selects y formularios.",
        security: [["sanctum" => []]],
        tags: ["Nómina"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de períodos activos obtenida exitosamente",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "nombre_periodo", type: "string", example: "Enero 2024 - Primera Quincena"),
                            new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2024-01-01"),
                            new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2024-01-15"),
                            new OA\Property(property: "estado", type: "string", example: "Abierto")
                        ],
                        type: "object"
                    )
                )
            ),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function activos(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $periodos = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre_periodo', 'fecha_inicio', 'fecha_fin', 'estado')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        return response()->json($periodos);
    }
}
