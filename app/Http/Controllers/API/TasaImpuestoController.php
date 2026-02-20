<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTasaImpuestoRequest;
use App\Http\Requests\UpdateTasaImpuestoRequest;
use App\Http\Requests\TasaImpuestoVigenteRequest;
use App\Http\Resources\TasaImpuestoResource;
use App\Models\TasaImpuesto;
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
    #[OA\Get(path: '/api/tasas-impuesto', summary: 'Listar tasas de impuesto', description: 'Listado paginado con filtros por tipo, activo, vigentes', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TasaImpuesto::class);

        $query = TasaImpuesto::where('eliminado', 0)->with('tipoImpuesto');

        if ($request->filled('tipo_impuesto_id')) {
            $query->where('tipo_impuesto_id', $request->tipo_impuesto_id);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        if ($request->filled('vigentes')) {
            $now = Carbon::now();
            $query->where('fecha_inicio_vigencia', '<=', $now)
                ->where(fn($q) => $q->whereNull('fecha_fin_vigencia')->orWhere('fecha_fin_vigencia', '>=', $now));
        }

        $query->orderBy($request->get('sort_by', 'fecha_inicio_vigencia'), $request->get('sort_order', 'desc'));

        return TasaImpuestoResource::collection($query->paginate($request->get('per_page', 15)));
    }

    #[OA\Post(path: '/api/tasas-impuesto', summary: 'Crear tasa de impuesto', description: 'Crea nueva tasa con vigencia temporal', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function store(StoreTasaImpuestoRequest $request): JsonResponse
    {
        $this->authorize('create', TasaImpuesto::class);

        $tasa = TasaImpuesto::create($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto creada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ], 201);
    }

    #[OA\Get(path: '/api/tasas-impuesto/{id}', summary: 'Obtener tasa de impuesto', description: 'Detalles de una tasa específica', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function show(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)->where('eliminado', 0)->with('tipoImpuesto')->firstOrFail();
        $this->authorize('view', $tasa);

        return response()->json(['success' => true, 'data' => new TasaImpuestoResource($tasa)]);
    }

    #[OA\Put(path: '/api/tasas-impuesto/{id}', summary: 'Actualizar tasa de impuesto', description: 'Actualiza datos de tasa existente', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function update(UpdateTasaImpuestoRequest $request, int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)->where('eliminado', 0)->firstOrFail();
        $this->authorize('update', $tasa);

        $tasa->update($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto actualizada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    #[OA\Delete(path: '/api/tasas-impuesto/{id}', summary: 'Eliminar tasa de impuesto', description: 'Eliminación lógica de tasa', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function destroy(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)->where('eliminado', 0)->firstOrFail();
        $this->authorize('delete', $tasa);

        $tasa->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json(['success' => true, 'message' => 'Tasa de impuesto eliminada exitosamente']);
    }

    #[OA\Get(path: '/api/tasas-impuesto/vigente', summary: 'Obtener tasa vigente', description: 'Tasa vigente para un tipo de impuesto en fecha específica', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function vigente(TasaImpuestoVigenteRequest $request): JsonResponse
    {
        $fecha = $request->filled('fecha') ? Carbon::parse($request->fecha) : Carbon::now();

        $tasa = TasaImpuesto::where('tipo_impuesto_id', $request->tipo_impuesto_id)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $fecha)
            ->where(fn($q) => $q->whereNull('fecha_fin_vigencia')->orWhere('fecha_fin_vigencia', '>=', $fecha))
            ->with('tipoImpuesto')
            ->first();

        if (!$tasa) {
            return response()->json(['success' => false, 'message' => 'No se encontró tasa vigente'], 404);
        }

        return response()->json(['success' => true, 'data' => new TasaImpuestoResource($tasa)]);
    }

    #[OA\Get(path: '/api/tasas-impuesto/vigentes-actuales', summary: 'Tasas vigentes actuales', description: 'Todas las tasas vigentes a fecha actual', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function vigentesActuales(): JsonResponse
    {
        $now = Carbon::now();

        $tasas = TasaImpuesto::where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $now)
            ->where(fn($q) => $q->whereNull('fecha_fin_vigencia')->orWhere('fecha_fin_vigencia', '>=', $now))
            ->with('tipoImpuesto')
            ->get();

        return response()->json(['success' => true, 'data' => TasaImpuestoResource::collection($tasas)]);
    }

    #[OA\Get(path: '/api/tasas-impuesto/historico/{tipoImpuestoId}', summary: 'Histórico de tasas', description: 'Histórico completo de tasas por tipo de impuesto', security: [['sanctum' => []]], tags: ['Catálogos Fiscales'])]
    public function historico(int $tipoImpuestoId): JsonResponse
    {
        $tasas = TasaImpuesto::where('tipo_impuesto_id', $tipoImpuestoId)
            ->where('eliminado', 0)
            ->with('tipoImpuesto')
            ->orderBy('fecha_inicio_vigencia', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => TasaImpuestoResource::collection($tasas)]);
    }
}
