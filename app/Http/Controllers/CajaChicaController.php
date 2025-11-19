<?php

namespace App\Http\Controllers;

use App\Models\CajaChica;
use App\Http\Requests\StoreCajaChicaRequest;
use App\Http\Requests\UpdateCajaChicaRequest;
use App\Http\Resources\CajaChicaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CajaChicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        // Filtros
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->responsable_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        // Ordenamiento por fecha descendente
        $query->orderBy('fecha', 'desc');

        $cajasChica = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($cajasChica),
            'meta' => [
                'current_page' => $cajasChica->currentPage(),
                'last_page' => $cajasChica->lastPage(),
                'per_page' => $cajasChica->perPage(),
                'total' => $cajasChica->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCajaChicaRequest $request): JsonResponse
    {
        $cajaChica = CajaChica::create([
            'empresa_id' => auth()->user()->empresa_id,
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
            'monto' => $request->monto,
            'tipo' => $request->tipo,
            'responsable_id' => $request->responsable_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento de caja chica registrado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCajaChicaRequest $request, CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $cajaChica->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Movimiento de caja chica actualizado exitosamente',
            'data' => new CajaChicaResource($cajaChica)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CajaChica $cajaChica): JsonResponse
    {
        if ($cajaChica->empresa_id !== auth()->user()->empresa_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        // Soft delete
        $cajaChica->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento de caja chica eliminado exitosamente'
        ]);
    }

    /**
     * Listar ingresos de caja chica.
     */
    public function ingresos(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('tipo', 'Ingreso')
            ->where('eliminado', 0);

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $ingresos = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($ingresos),
            'total_ingresos' => $ingresos->sum('monto')
        ]);
    }

    /**
     * Listar egresos de caja chica.
     */
    public function egresos(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('tipo', 'Egreso')
            ->where('eliminado', 0);

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $egresos = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($egresos),
            'total_egresos' => $egresos->sum('monto')
        ]);
    }

    /**
     * Listar movimientos por responsable.
     */
    public function porResponsable(Request $request, int $responsableId): JsonResponse
    {
        $movimientos = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('responsable_id', $responsableId)
            ->where('eliminado', 0)
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CajaChicaResource::collection($movimientos),
            'total_ingresos' => $movimientos->where('tipo', 'Ingreso')->sum('monto'),
            'total_egresos' => $movimientos->where('tipo', 'Egreso')->sum('monto'),
        ]);
    }

    /**
     * Resumen de movimientos por tipo.
     */
    public function resumenPorTipo(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        if ($request->filled('fecha_desde')) {
            $query->where('fecha', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $resumen = $query->select('tipo', DB::raw('count(*) as total_movimientos'), DB::raw('sum(monto) as total_monto'))
            ->groupBy('tipo')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Calcular saldo actual de caja chica.
     */
    public function saldoActual(Request $request): JsonResponse
    {
        $query = CajaChica::where('empresa_id', auth()->user()->empresa_id)
            ->where('eliminado', 0);

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha', '<=', $request->fecha_hasta);
        }

        $ingresos = $query->clone()->where('tipo', 'Ingreso')->sum('monto');
        $egresos = $query->clone()->where('tipo', 'Egreso')->sum('monto');
        $saldo = $ingresos - $egresos;

        return response()->json([
            'success' => true,
            'data' => [
                'total_ingresos' => $ingresos,
                'total_egresos' => $egresos,
                'saldo_actual' => $saldo,
                'fecha_consulta' => $request->filled('fecha_hasta') ? $request->fecha_hasta : now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
