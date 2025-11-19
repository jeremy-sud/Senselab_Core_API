<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\DetalleAsientoResource;
use App\Models\DetalleAsiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Controlador API para Detalles de Asientos Contables
 *
 * Gestiona las líneas individuales de los asientos contables (debe/haber por cuenta).
 * Normalmente se gestionan a través del AsientoContableController, pero este controlador
 * permite consultas específicas sobre los movimientos.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleAsientoController extends Controller
{
    /**
     * Listar todos los detalles de asientos de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable']);

        // Filtro por asiento contable
        if ($request->filled('asiento_contable_id')) {
            $query->where('asiento_contable_id', $request->asiento_contable_id);
        }

        // Filtro por cuenta contable
        if ($request->filled('cuenta_contable_id')) {
            $query->where('cuenta_contable_id', $request->cuenta_contable_id);
        }

        // Filtro solo movimientos al debe
        if ($request->filled('solo_debe') && $request->solo_debe == 1) {
            $query->where('debe', '>', 0);
        }

        // Filtro solo movimientos al haber
        if ($request->filled('solo_haber') && $request->solo_haber == 1) {
            $query->where('haber', '>', 0);
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'created_at'), $request->get('sort_order', 'desc'));

        $detalles = $query->paginate($request->get('per_page', 15));

        return DetalleAsientoResource::collection($detalles);
    }

    /**
     * Mostrar un detalle de asiento específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $detalle = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            })
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new DetalleAsientoResource($detalle)
        ]);
    }

    /**
     * Obtener movimientos (debe y haber) de una cuenta contable específica
     *
     * @param int $cuentaContableId
     * @param Request $request
     * @return JsonResponse
     */
    public function porCuenta(int $cuentaContableId, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado'); // Solo asientos mayorizados
            })
            ->where('cuenta_contable_id', $cuentaContableId)
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable']);

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereHas('asientoContable', function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->desde, $request->hasta]);
            });
        }

        $detalles = $query->orderBy('created_at', 'desc')->get();

        $totalDebe = $detalles->sum('debe');
        $totalHaber = $detalles->sum('haber');
        $saldo = $totalDebe - $totalHaber;

        return response()->json([
            'success' => true,
            'data' => [
                'movimientos' => DetalleAsientoResource::collection($detalles),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'saldo' => $saldo
            ]
        ]);
    }

    /**
     * Obtener libro mayor (mayor analítico) de todas las cuentas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function libroMayor(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde'
        ]);

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId, $request) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado');
                
                if ($request->filled('desde') && $request->filled('hasta')) {
                    $q->whereBetween('fecha', [$request->desde, $request->hasta]);
                }
            })
            ->where('eliminado', 0)
            ->with(['asientoContable', 'cuentaContable'])
            ->orderBy('cuenta_contable_id')
            ->orderBy('created_at')
            ->get();

        // Agrupar por cuenta contable
        $libroMayor = $query->groupBy('cuenta_contable_id')->map(function ($detalles) {
            $totalDebe = $detalles->sum('debe');
            $totalHaber = $detalles->sum('haber');
            
            return [
                'cuenta_contable' => $detalles->first()->cuentaContable,
                'movimientos' => DetalleAsientoResource::collection($detalles),
                'total_debe' => $totalDebe,
                'total_haber' => $totalHaber,
                'saldo' => $totalDebe - $totalHaber,
                'cantidad_movimientos' => $detalles->count()
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $libroMayor
        ]);
    }

    /**
     * Obtener balance de comprobación
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function balanceComprobacion(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date|after_or_equal:desde'
        ]);

        $query = DetalleAsiento::whereHas('asientoContable', function ($q) use ($empresaId, $request) {
                $q->where('empresa_id', $empresaId)
                  ->where('estado', 'Mayorizado');
                
                if ($request->filled('desde') && $request->filled('hasta')) {
                    $q->whereBetween('fecha', [$request->desde, $request->hasta]);
                }
            })
            ->where('eliminado', 0)
            ->selectRaw('cuenta_contable_id, SUM(debe) as total_debe, SUM(haber) as total_haber')
            ->groupBy('cuenta_contable_id')
            ->with('cuentaContable.tipoCuenta')
            ->get();

        $balance = $query->map(function ($item) {
            $saldo = $item->total_debe - $item->total_haber;
            
            return [
                'cuenta_contable' => $item->cuentaContable,
                'total_debe' => $item->total_debe,
                'total_haber' => $item->total_haber,
                'saldo_deudor' => $saldo > 0 ? $saldo : 0,
                'saldo_acreedor' => $saldo < 0 ? abs($saldo) : 0
            ];
        });

        $totales = [
            'total_debe' => $query->sum('total_debe'),
            'total_haber' => $query->sum('total_haber'),
            'total_saldos_deudores' => $balance->sum('saldo_deudor'),
            'total_saldos_acreedores' => $balance->sum('saldo_acreedor')
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'totales' => $totales
            ]
        ]);
    }
}
