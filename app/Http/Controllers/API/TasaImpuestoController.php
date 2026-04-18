<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTasaImpuestoRequest;
use App\Http\Requests\UpdateTasaImpuestoRequest;
use App\Http\Requests\TasaImpuestoVigenteRequest;
use App\Http\Resources\TasaImpuestoResource;
use App\Models\TasaImpuesto;
use App\Services\TasaImpuestoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

/**
 * TasaImpuestoController - Versión Refactorizada (FASE 4.2)
 *
 * Gestiona tasas de impuestos con vigencia temporal.
 * Reducción de líneas: 623 → ~180 (-71%)
 *
 * Refactorización: 13 de febrero de 2026
 */
class TasaImpuestoController extends Controller
{
    public function __construct(
        private readonly TasaImpuestoService $service
    ) {}

    #[OA\Get(
        path: '/api/tasas-impuesto',
        summary: 'Listar tasas de impuesto',
        description: 'Listado paginado con filtros por tipo, activo, vigentes',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Listado obtenido exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TasaImpuesto::class);

        $filtros = array_filter([
            'tipo_impuesto_id' => $request->input('tipo_impuesto_id'),
            'activo' => $request->input('activo'),
            'vigentes' => $request->input('vigentes'),
            'sort_by' => $request->get('sort_by'),
            'sort_order' => $request->get('sort_order'),
        ], fn ($v) => $v !== null);

        $perPage = (int) $request->get('per_page', 15);

        return TasaImpuestoResource::collection($this->service->listar($filtros, $perPage));
    }

    #[OA\Post(
        path: '/api/tasas-impuesto',
        summary: 'Crear tasa de impuesto',
        description: 'Crea nueva tasa con vigencia temporal',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 201, description: 'Tasa creada exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function store(StoreTasaImpuestoRequest $request): JsonResponse
    {
        $this->authorize('create', TasaImpuesto::class);

        $tasa = $this->service->crear($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto creada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ], 201);
    }

    #[OA\Get(
        path: '/api/tasas-impuesto/{id}',
        summary: 'Obtener tasa de impuesto',
        description: 'Detalles de una tasa específica',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Tasa obtenida exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Tasa no encontrada')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $tasa = $this->service->obtener($id);
        $this->authorize('view', $tasa);

        return response()->json(['success' => true, 'data' => new TasaImpuestoResource($tasa)]);
    }

    #[OA\Put(
        path: '/api/tasas-impuesto/{id}',
        summary: 'Actualizar tasa de impuesto',
        description: 'Actualiza datos de tasa existente',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Tasa actualizada exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Tasa no encontrada'),
            new OA\Response(response: 422, description: 'Error de validación')
        ]
    )]
    public function update(UpdateTasaImpuestoRequest $request, int $id): JsonResponse
    {
        $tasa = $this->service->obtener($id);
        $this->authorize('update', $tasa);

        $tasa = $this->service->actualizar($tasa, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto actualizada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    #[OA\Delete(
        path: '/api/tasas-impuesto/{id}',
        summary: 'Eliminar tasa de impuesto',
        description: 'Eliminación lógica de tasa',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Tasa eliminada exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Tasa no encontrada')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $tasa = $this->service->obtener($id);
        $this->authorize('delete', $tasa);

        $this->service->eliminar($tasa);

        return response()->json(['success' => true, 'message' => 'Tasa de impuesto eliminada exitosamente']);
    }

    #[OA\Get(
        path: '/api/tasas-impuesto/vigente',
        summary: 'Obtener tasa vigente',
        description: 'Tasa vigente para un tipo de impuesto en fecha específica',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Tasa vigente encontrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No se encontró tasa vigente')
        ]
    )]
    public function vigente(TasaImpuestoVigenteRequest $request): JsonResponse
    {
        $fecha = $request->filled('fecha') ? Carbon::parse($request->fecha) : null;

        $tasa = $this->service->vigente((int) $request->tipo_impuesto_id, $fecha);

        if (!$tasa) {
            return response()->json(['success' => false, 'message' => 'No se encontró tasa vigente'], 404);
        }

        return response()->json(['success' => true, 'data' => new TasaImpuestoResource($tasa)]);
    }

    #[OA\Get(
        path: '/api/tasas-impuesto/vigentes-actuales',
        summary: 'Tasas vigentes actuales',
        description: 'Todas las tasas vigentes a fecha actual',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Tasas vigentes obtenidas'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function vigentesActuales(): JsonResponse
    {
        $tasas = $this->service->vigentesActuales();

        return response()->json(['success' => true, 'data' => TasaImpuestoResource::collection($tasas)]);
    }

    #[OA\Get(
        path: '/api/tasas-impuesto/historico/{tipoImpuestoId}',
        summary: 'Histórico de tasas',
        description: 'Histórico completo de tasas por tipo de impuesto',
        security: [['sanctum' => []]],
        tags: ['Catálogos Fiscales'],
        responses: [
            new OA\Response(response: 200, description: 'Histórico obtenido exitosamente'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function historico(int $tipoImpuestoId): JsonResponse
    {
        $tasas = $this->service->historico($tipoImpuestoId);

        return response()->json(['success' => true, 'data' => TasaImpuestoResource::collection($tasas)]);
    }
}
