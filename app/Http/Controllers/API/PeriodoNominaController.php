<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePeriodoNominaRequest;
use App\Http\Requests\UpdatePeriodoNominaRequest;
use App\Http\Resources\PeriodoNominaResource;
use App\Models\PeriodoNomina;
use App\Services\PeriodoNominaService;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

/**
 * PeriodoNominaController - Versión Refactorizada (FASE 8)
 *
 * Controlador simplificado usando Service Layer Pattern.
 * Delegación: Validación → Service → Response
 *
 * Reducción de líneas: 617 → ~290 (-53%)
 * Refactorización completada: FASE 8
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A.
 */
class PeriodoNominaController extends Controller
{
    use HasEmpresaContext;

    public function __construct(private PeriodoNominaService $periodoNominaService) {}

    /**
     * GET /api/periodos-nomina
     * Listar períodos de nómina con filtros
     */
    #[OA\Get(
        path: '/api/periodos-nomina',
        summary: 'Listar períodos de nómina',
        description: 'Obtiene la lista paginada de períodos de nómina de la empresa. Permite filtrar por estado, año y mes.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['Abierto', 'Cerrado', 'Procesado'])),
            new OA\Parameter(name: 'anio', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 2024)),
            new OA\Parameter(name: 'mes', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 12))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de períodos obtenida exitosamente', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))])),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PeriodoNomina::class);

        $periodos = $this->periodoNominaService->listar(
            empresaId: $this->getEmpresaId(),
            filtros: $request->only(['estado', 'anio', 'mes'])
        );

        return PeriodoNominaResource::collection($periodos);
    }

    /**
     * POST /api/periodos-nomina
     * Crear un nuevo período de nómina
     */
    #[OA\Post(
        path: '/api/periodos-nomina',
        summary: 'Crear período de nómina',
        description: 'Crea un nuevo período de nómina en estado Abierto. Valida que no haya solapamiento de fechas.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre_periodo', 'fecha_inicio', 'fecha_fin', 'fecha_pago_estimada'],
                properties: [
                    new OA\Property(property: 'nombre_periodo', type: 'string', maxLength: 100, example: 'Enero 2024 - Primera Quincena'),
                    new OA\Property(property: 'fecha_inicio', type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'fecha_fin', type: 'string', format: 'date', example: '2024-01-15'),
                    new OA\Property(property: 'fecha_pago_estimada', type: 'string', format: 'date', example: '2024-01-17'),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Período de nómina creado exitosamente'),
            new OA\Response(response: 422, description: 'Datos de validación incorrectos')
        ]
    )]
    public function store(StorePeriodoNominaRequest $request): PeriodoNominaResource
    {
        $this->authorize('create', PeriodoNomina::class);

        $periodo = $this->periodoNominaService->crear(
            empresaId: $this->getEmpresaId(),
            data: $request->validated()
        );

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * GET /api/periodos-nomina/{id}
     * Detalle de un período con sus pagos
     */
    #[OA\Get(
        path: '/api/periodos-nomina/{id}',
        summary: 'Obtener período de nómina',
        description: 'Obtiene el detalle de un período de nómina con sus pagos asociados.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Período obtenido exitosamente'),
            new OA\Response(response: 404, description: 'Período no encontrado')
        ]
    )]
    public function show(int $id): PeriodoNominaResource
    {
        $periodo = $this->periodoNominaService->obtener(
            empresaId: $this->getEmpresaId(),
            id: $id
        );

        $this->authorize('view', $periodo);

        return new PeriodoNominaResource($periodo);
    }

    /**
     * PUT /api/periodos-nomina/{id}
     * Actualizar un período (no procesados)
     */
    #[OA\Put(
        path: '/api/periodos-nomina/{id}',
        summary: 'Actualizar período de nómina',
        description: 'Actualiza un período existente. No permite modificar períodos en estado Procesado.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre_periodo', type: 'string', maxLength: 100),
                    new OA\Property(property: 'fecha_inicio', type: 'string', format: 'date'),
                    new OA\Property(property: 'fecha_fin', type: 'string', format: 'date'),
                    new OA\Property(property: 'fecha_pago_estimada', type: 'string', format: 'date'),
                    new OA\Property(property: 'observaciones', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Período actualizado exitosamente'),
            new OA\Response(response: 422, description: 'No se puede modificar un período procesado')
        ]
    )]
    public function update(UpdatePeriodoNominaRequest $request, int $id): PeriodoNominaResource
    {
        $empresaId = $this->getEmpresaId();

        // Obtener el período primero para autorizar
        $periodo = $this->periodoNominaService->obtener($empresaId, $id);
        $this->authorize('update', $periodo);

        $periodo = $this->periodoNominaService->actualizar(
            empresaId: $empresaId,
            id: $id,
            data: $request->validated()
        );

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * DELETE /api/periodos-nomina/{id}
     * Eliminar período (sin pagos asociados)
     */
    #[OA\Delete(
        path: '/api/periodos-nomina/{id}',
        summary: 'Eliminar período de nómina',
        description: 'Elimina lógicamente un período. No permite eliminar períodos con pagos asociados.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Período eliminado exitosamente'),
            new OA\Response(response: 422, description: 'No se puede eliminar un período con pagos asociados')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $empresaId = $this->getEmpresaId();
        $periodo = $this->periodoNominaService->obtener($empresaId, $id);
        $this->authorize('delete', $periodo);

        $this->periodoNominaService->eliminar($empresaId, $id);

        return response()->json([
            'message' => 'Período de nómina eliminado exitosamente',
        ]);
    }

    /**
     * POST /api/periodos-nomina/{id}/cerrar
     * Cerrar un período (Abierto → Cerrado)
     */
    #[OA\Post(
        path: '/api/periodos-nomina/{id}/cerrar',
        summary: 'Cerrar período de nómina',
        description: 'Cambia el estado de Abierto a Cerrado.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Período cerrado exitosamente'),
            new OA\Response(response: 422, description: 'Solo se pueden cerrar períodos en estado Abierto')
        ]
    )]
    public function cerrar(int $id): PeriodoNominaResource
    {
        $periodo = $this->periodoNominaService->cerrar(
            empresaId: $this->getEmpresaId(),
            id: $id
        );

        return new PeriodoNominaResource($periodo->load(['empresa']));
    }

    /**
     * POST /api/periodos-nomina/{id}/procesar
     * Procesar un período (→ Procesado, genera pagos)
     */
    #[OA\Post(
        path: '/api/periodos-nomina/{id}/procesar',
        summary: 'Procesar período de nómina',
        description: 'Cambia el estado a Procesado y genera los pagos de nómina. Acción irreversible.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Período procesado exitosamente'),
            new OA\Response(response: 422, description: 'Este período ya ha sido procesado'),
            new OA\Response(response: 500, description: 'Error al procesar')
        ]
    )]
    public function procesar(int $id): JsonResponse
    {
        try {
            $periodo = $this->periodoNominaService->procesar(
                empresaId: $this->getEmpresaId(),
                id: $id
            );

            return response()->json([
                'message' => 'Período de nómina procesado exitosamente',
                'periodo' => new PeriodoNominaResource($periodo),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el período de nómina',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/periodos-nomina/{id}/resumen
     * Resumen de totales del período
     */
    #[OA\Get(
        path: '/api/periodos-nomina/{id}/resumen',
        summary: 'Resumen del período',
        description: 'Estadísticas y totales: empleados, bruto, deducciones, neto.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Resumen obtenido exitosamente', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'periodo', type: 'object'),
                    new OA\Property(property: 'resumen', type: 'object')
                ]
            ))
        ]
    )]
    public function resumen(int $id): JsonResponse
    {
        return response()->json(
            $this->periodoNominaService->resumen(
                empresaId: $this->getEmpresaId(),
                id: $id
            )
        );
    }

    /**
     * GET /api/periodos-nomina/activos
     * Lista simplificada para selects
     */
    #[OA\Get(
        path: '/api/periodos-nomina/activos',
        summary: 'Listar períodos activos',
        description: 'Lista simplificada de períodos activos para selects y formularios.',
        security: [['sanctum' => []]],
        tags: ['Nómina'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de períodos activos', content: new OA\JsonContent(type: 'array', items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'nombre_periodo', type: 'string'),
                    new OA\Property(property: 'estado', type: 'string')
                ],
                type: 'object'
            )))
        ]
    )]
    public function activos(): JsonResponse
    {
        return response()->json(
            $this->periodoNominaService->activos($this->getEmpresaId())
        );
    }
}
