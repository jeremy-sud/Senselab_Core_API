<?php

namespace App\Http\Controllers;

use App\Models\MovimientoCajaChica;
use App\Models\CajaChica;
use App\Http\Requests\StoreMovimientoCajaChicaRequest;
use App\Http\Requests\UpdateMovimientoCajaChicaRequest;
use App\Http\Resources\MovimientoCajaChicaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MovimientoCajaChicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MovimientoCajaChica::query();

        // Filtros
        if ($request->filled('caja_chica_id')) {
            $query->where('caja_chica_id', $request->caja_chica_id);
        }

        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $query->where('eliminado', 0);

        // Ordenamiento por fecha descendente
        $query->orderBy('fecha_movimiento', 'desc');

        $movimientos = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => MovimientoCajaChicaResource::collection($movimientos),
            'meta' => [
                'current_page' => $movimientos->currentPage(),
                'last_page' => $movimientos->lastPage(),
                'per_page' => $movimientos->perPage(),
                'total' => $movimientos->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMovimientoCajaChicaRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $movimiento = MovimientoCajaChica::create($request->validated());

            // Actualizar saldo de la caja chica
            $this->actualizarSaldoCajaChica($movimiento->caja_chica_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de caja chica registrado exitosamente',
                'data' => new MovimientoCajaChicaResource($movimiento)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MovimientoCajaChica $movimientoCajaChica): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new MovimientoCajaChicaResource($movimientoCajaChica)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMovimientoCajaChicaRequest $request, MovimientoCajaChica $movimientoCajaChica): JsonResponse
    {
        DB::beginTransaction();
        try {
            $movimientoCajaChica->update($request->validated());

            // Recalcular saldo de la caja chica
            $this->actualizarSaldoCajaChica($movimientoCajaChica->caja_chica_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de caja chica actualizado exitosamente',
                'data' => new MovimientoCajaChicaResource($movimientoCajaChica)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MovimientoCajaChica $movimientoCajaChica): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Soft delete
            $movimientoCajaChica->update(['eliminado' => 1, 'activo' => 0]);

            // Recalcular saldo de la caja chica
            $this->actualizarSaldoCajaChica($movimientoCajaChica->caja_chica_id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Movimiento de caja chica eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar movimientos por caja chica.
     */
    public function porCaja(int $cajaChicaId): JsonResponse
    {
        $movimientos = MovimientoCajaChica::where('caja_chica_id', $cajaChicaId)
            ->where('eliminado', 0)
            ->orderBy('fecha_movimiento', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => MovimientoCajaChicaResource::collection($movimientos)
        ]);
    }

    /**
     * Listar movimientos por tipo.
     */
    public function porTipo(Request $request, string $tipo): JsonResponse
    {
        $query = MovimientoCajaChica::where('tipo_movimiento', $tipo)
            ->where('eliminado', 0);

        if ($request->filled('caja_chica_id')) {
            $query->where('caja_chica_id', $request->caja_chica_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('fecha_movimiento', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => MovimientoCajaChicaResource::collection($movimientos),
            'total' => $movimientos->sum('monto')
        ]);
    }

    /**
     * Resumen de totales por tipo de movimiento.
     */
    public function totalPorTipo(Request $request): JsonResponse
    {
        $query = MovimientoCajaChica::where('eliminado', 0);

        if ($request->filled('caja_chica_id')) {
            $query->where('caja_chica_id', $request->caja_chica_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $resumen = $query->selectRaw('tipo_movimiento, count(*) as total_movimientos, sum(monto) as total_monto')
            ->groupBy('tipo_movimiento')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }

    /**
     * Actualizar el saldo de una caja chica basado en sus movimientos.
     */
    private function actualizarSaldoCajaChica(int $cajaChicaId): void
    {
        $cajaChica = CajaChica::findOrFail($cajaChicaId);

        $ingresos = MovimientoCajaChica::where('caja_chica_id', $cajaChicaId)
            ->whereIn('tipo_movimiento', ['Ingreso', 'Reembolso'])
            ->where('eliminado', 0)
            ->sum('monto');

        $egresos = MovimientoCajaChica::where('caja_chica_id', $cajaChicaId)
            ->where('tipo_movimiento', 'Egreso')
            ->where('eliminado', 0)
            ->sum('monto');

        $ajustes = MovimientoCajaChica::where('caja_chica_id', $cajaChicaId)
            ->where('tipo_movimiento', 'Ajuste')
            ->where('eliminado', 0)
            ->sum('monto');

        $saldoActual = $cajaChica->monto_inicial + $ingresos - $egresos + $ajustes;

        $cajaChica->update(['saldo_actual' => $saldoActual]);
    }
}
