<?php

namespace App\Http\Controllers;

use App\Models\TipoCambioHistorial;
use App\Http\Requests\StoreTipoCambioHistorialRequest;
use App\Http\Requests\UpdateTipoCambioHistorialRequest;
use App\Http\Resources\TipoCambioHistorialResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TipoCambioHistorialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TipoCambioHistorial::query();

        // Filtros
        if ($request->filled('moneda_origen')) {
            $query->where('moneda_origen', strtoupper($request->moneda_origen));
        }

        if ($request->filled('moneda_destino')) {
            $query->where('moneda_destino', strtoupper($request->moneda_destino));
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        if ($request->filled('fuente')) {
            $query->where('fuente', $request->fuente);
        }

        // Ordenamiento por fecha descendente
        $query->orderBy('fecha', 'desc');

        $tipos = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => TipoCambioHistorialResource::collection($tipos),
            'meta' => [
                'current_page' => $tipos->currentPage(),
                'last_page' => $tipos->lastPage(),
                'per_page' => $tipos->perPage(),
                'total' => $tipos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoCambioHistorialRequest $request): JsonResponse
    {
        $tipoCambio = TipoCambioHistorial::create([
            'fecha' => $request->fecha,
            'moneda_origen' => strtoupper($request->moneda_origen),
            'moneda_destino' => strtoupper($request->moneda_destino),
            'tasa_compra' => $request->tasa_compra,
            'tasa_venta' => $request->tasa_venta,
            'fuente' => $request->fuente ?? 'BCCR',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cambio registrado exitosamente',
            'data' => new TipoCambioHistorialResource($tipoCambio)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoCambioHistorial $tipoCambioHistorial): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new TipoCambioHistorialResource($tipoCambioHistorial)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoCambioHistorialRequest $request, TipoCambioHistorial $tipoCambioHistorial): JsonResponse
    {
        $tipoCambioHistorial->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cambio actualizado exitosamente',
            'data' => new TipoCambioHistorialResource($tipoCambioHistorial)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoCambioHistorial $tipoCambioHistorial): JsonResponse
    {
        $tipoCambioHistorial->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tipo de cambio eliminado exitosamente'
        ]);
    }

    /**
     * Obtiene el tipo de cambio vigente para una fecha específica.
     */
    public function vigente(Request $request): JsonResponse
    {
        $request->validate([
            'fecha' => 'nullable|date',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
        ]);

        $fecha = $request->filled('fecha') ? $request->fecha : Carbon::now()->format('Y-m-d');
        $monedaOrigen = strtoupper($request->moneda_origen);
        $monedaDestino = strtoupper($request->moneda_destino);

        // Buscar el tipo de cambio más reciente hasta la fecha especificada
        $tipoCambio = TipoCambioHistorial::where('moneda_origen', $monedaOrigen)
            ->where('moneda_destino', $monedaDestino)
            ->where('fecha', '<=', $fecha)
            ->orderBy('fecha', 'desc')
            ->first();

        if (!$tipoCambio) {
            return response()->json([
                'success' => false,
                'message' => "No hay tipo de cambio disponible para {$monedaOrigen}/{$monedaDestino} hasta la fecha {$fecha}"
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TipoCambioHistorialResource($tipoCambio)
        ]);
    }

    /**
     * Convertir un monto entre monedas usando el tipo de cambio vigente.
     */
    public function convertir(Request $request): JsonResponse
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'fecha' => 'nullable|date',
            'usar_tasa' => 'nullable|in:compra,venta',
        ]);

        $fecha = $request->filled('fecha') ? $request->fecha : Carbon::now()->format('Y-m-d');
        $monedaOrigen = strtoupper($request->moneda_origen);
        $monedaDestino = strtoupper($request->moneda_destino);
        $usarTasa = $request->usar_tasa ?? 'venta';

        // Buscar tipo de cambio
        $tipoCambio = TipoCambioHistorial::where('moneda_origen', $monedaOrigen)
            ->where('moneda_destino', $monedaDestino)
            ->where('fecha', '<=', $fecha)
            ->orderBy('fecha', 'desc')
            ->first();

        if (!$tipoCambio) {
            return response()->json([
                'success' => false,
                'message' => "No hay tipo de cambio disponible para {$monedaOrigen}/{$monedaDestino}"
            ], 404);
        }

        $tasa = $usarTasa === 'compra' ? $tipoCambio->tasa_compra : $tipoCambio->tasa_venta;
        $montoConvertido = $request->monto * $tasa;

        return response()->json([
            'success' => true,
            'data' => [
                'monto_original' => $request->monto,
                'moneda_origen' => $monedaOrigen,
                'monto_convertido' => round($montoConvertido, 2),
                'moneda_destino' => $monedaDestino,
                'tasa_usada' => $tasa,
                'tipo_tasa' => $usarTasa,
                'fecha_tasa' => $tipoCambio->fecha,
                'fuente' => $tipoCambio->fuente,
            ]
        ]);
    }

    /**
     * Listar tipos de cambio por moneda.
     */
    public function porMoneda(Request $request): JsonResponse
    {
        $request->validate([
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'limite' => 'nullable|integer|min:1|max:100',
        ]);

        $monedaOrigen = strtoupper($request->moneda_origen);
        $monedaDestino = strtoupper($request->moneda_destino);
        $limite = $request->limite ?? 30;

        $tipos = TipoCambioHistorial::where('moneda_origen', $monedaOrigen)
            ->where('moneda_destino', $monedaDestino)
            ->orderBy('fecha', 'desc')
            ->limit($limite)
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCambioHistorialResource::collection($tipos)
        ]);
    }

    /**
     * Obtener tipos de cambio para una fecha específica.
     */
    public function porFecha(Request $request, string $fecha): JsonResponse
    {
        try {
            Carbon::parse($fecha);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Formato de fecha inválido'
            ], 422);
        }

        $tipos = TipoCambioHistorial::where('fecha', $fecha)
            ->get();

        return response()->json([
            'success' => true,
            'data' => TipoCambioHistorialResource::collection($tipos)
        ]);
    }

    /**
     * Obtener tendencia de un par de monedas.
     */
    public function tendencia(Request $request): JsonResponse
    {
        $request->validate([
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'dias' => 'nullable|integer|min:7|max:365',
        ]);

        $monedaOrigen = strtoupper($request->moneda_origen);
        $monedaDestino = strtoupper($request->moneda_destino);
        $dias = $request->dias ?? 30;

        $fechaInicio = Carbon::now()->subDays($dias)->format('Y-m-d');

        $tipos = TipoCambioHistorial::where('moneda_origen', $monedaOrigen)
            ->where('moneda_destino', $monedaDestino)
            ->where('fecha', '>=', $fechaInicio)
            ->orderBy('fecha', 'asc')
            ->get();

        if ($tipos->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos suficientes para calcular tendencia'
            ], 404);
        }

        $tasaPromedio = $tipos->avg('tasa_venta');
        $tasaMinima = $tipos->min('tasa_venta');
        $tasaMaxima = $tipos->max('tasa_venta');
        $tasaActual = $tipos->last()->tasa_venta;
        $variacion = (($tasaActual - $tipos->first()->tasa_venta) / $tipos->first()->tasa_venta) * 100;

        return response()->json([
            'success' => true,
            'data' => [
                'moneda_origen' => $monedaOrigen,
                'moneda_destino' => $monedaDestino,
                'periodo_dias' => $dias,
                'tasa_actual' => $tasaActual,
                'tasa_promedio' => round($tasaPromedio, 5),
                'tasa_minima' => $tasaMinima,
                'tasa_maxima' => $tasaMaxima,
                'variacion_porcentual' => round($variacion, 2),
                'datos_historicos' => TipoCambioHistorialResource::collection($tipos),
            ]
        ]);
    }
}
