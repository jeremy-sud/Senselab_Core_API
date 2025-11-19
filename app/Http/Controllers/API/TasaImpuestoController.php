<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTasaImpuestoRequest;
use App\Http\Requests\UpdateTasaImpuestoRequest;
use App\Http\Resources\TasaImpuestoResource;
use App\Models\TasaImpuesto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Carbon\Carbon;

/**
 * Controlador API para Tasas de Impuesto
 *
 * Gestiona las tasas de impuestos con vigencia temporal.
 * Permite mantener histórico de cambios en tasas (ej: IVA 13% -> 15%).
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TasaImpuestoController extends Controller
{
    /**
     * Listar todas las tasas de impuesto
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TasaImpuesto::where('eliminado', 0)->with('tipoImpuesto');

        // Filtro por tipo de impuesto
        if ($request->filled('tipo_impuesto_id')) {
            $query->where('tipo_impuesto_id', $request->tipo_impuesto_id);
        }

        // Filtro por estado activo
        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        // Filtro por vigencia actual
        if ($request->filled('vigentes')) {
            $now = Carbon::now();
            $query->where('fecha_inicio_vigencia', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('fecha_fin_vigencia')
                      ->orWhere('fecha_fin_vigencia', '>=', $now);
                });
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'fecha_inicio_vigencia'), $request->get('sort_order', 'desc'));

        $tasas = $query->paginate($request->get('per_page', 15));

        return TasaImpuestoResource::collection($tasas);
    }

    /**
     * Crear una nueva tasa de impuesto
     *
     * @param StoreTasaImpuestoRequest $request
     * @return JsonResponse
     */
    public function store(StoreTasaImpuestoRequest $request): JsonResponse
    {
        $tasa = TasaImpuesto::create($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto creada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ], 201);
    }

    /**
     * Mostrar una tasa de impuesto específica
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->with('tipoImpuesto')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Actualizar una tasa de impuesto existente
     *
     * @param UpdateTasaImpuestoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTasaImpuestoRequest $request, int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tasa->update($request->validated());
        $tasa->load('tipoImpuesto');

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto actualizada exitosamente',
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Eliminar (soft delete) una tasa de impuesto
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $tasa = TasaImpuesto::where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $tasa->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Tasa de impuesto eliminada exitosamente'
        ]);
    }

    /**
     * Obtener tasa vigente para un tipo de impuesto en una fecha específica
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vigente(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_impuesto_id' => 'required|integer|exists:tipos_impuesto,id',
            'fecha' => 'nullable|date'
        ]);

        $fecha = $request->filled('fecha') ? Carbon::parse($request->fecha) : Carbon::now();

        $tasa = TasaImpuesto::where('tipo_impuesto_id', $request->tipo_impuesto_id)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $fecha)
            ->where(function ($q) use ($fecha) {
                $q->whereNull('fecha_fin_vigencia')
                  ->orWhere('fecha_fin_vigencia', '>=', $fecha);
            })
            ->with('tipoImpuesto')
            ->first();

        if (!$tasa) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una tasa vigente para la fecha especificada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TasaImpuestoResource($tasa)
        ]);
    }

    /**
     * Obtener todas las tasas vigentes actuales
     *
     * @return JsonResponse
     */
    public function vigentesActuales(): JsonResponse
    {
        $now = Carbon::now();

        $tasas = TasaImpuesto::where('eliminado', 0)
            ->where('activo', 1)
            ->where('fecha_inicio_vigencia', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('fecha_fin_vigencia')
                  ->orWhere('fecha_fin_vigencia', '>=', $now);
            })
            ->with('tipoImpuesto')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TasaImpuestoResource::collection($tasas)
        ]);
    }

    /**
     * Obtener histórico de tasas para un tipo de impuesto
     *
     * @param int $tipoImpuestoId
     * @return JsonResponse
     */
    public function historico(int $tipoImpuestoId): JsonResponse
    {
        $tasas = TasaImpuesto::where('tipo_impuesto_id', $tipoImpuestoId)
            ->where('eliminado', 0)
            ->with('tipoImpuesto')
            ->orderBy('fecha_inicio_vigencia', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TasaImpuestoResource::collection($tasas)
        ]);
    }
}
