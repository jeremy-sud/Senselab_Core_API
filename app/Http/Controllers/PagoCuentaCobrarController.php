<?php

namespace App\Http\Controllers;

use App\Models\PagoCuentaCobrar;
use App\Models\CuentaPorCobrar;
use App\Http\Requests\StorePagoCuentaCobrarRequest;
use App\Http\Requests\UpdatePagoCuentaCobrarRequest;
use App\Http\Resources\PagoCuentaCobrarResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PagoCuentaCobrarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Multi-tenancy: Filtrar por empresa del usuario autenticado
        $empresaId = $request->user()->empresa_id;
        
        $query = PagoCuentaCobrar::where('eliminado', 0)
            ->whereHas('cuentaPorCobrar', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });

        if ($request->filled('cuenta_por_cobrar_id')) {
            $query->where('cuenta_por_cobrar_id', $request->cuenta_por_cobrar_id);
        }

        if ($request->filled('forma_pago_id')) {
            $query->where('forma_pago_id', $request->forma_pago_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_pago', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_pago', '<=', $request->fecha_hasta);
        }

        $query->orderBy('fecha_pago', 'desc');

        $pagos = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => PagoCuentaCobrarResource::collection($pagos),
            'meta' => [
                'current_page' => $pagos->currentPage(),
                'last_page' => $pagos->lastPage(),
                'per_page' => $pagos->perPage(),
                'total' => $pagos->total(),
            ]
        ]);
    }

    public function store(StorePagoCuentaCobrarRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $pago = PagoCuentaCobrar::create($request->validated());

            // Actualizar monto pagado en cuenta por cobrar
            $cuenta = CuentaPorCobrar::findOrFail($pago->cuenta_por_cobrar_id);
            $cuenta->monto_pagado += $pago->monto_pago;
            $cuenta->saldo = $cuenta->monto_total - $cuenta->monto_pagado;
            $cuenta->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'data' => new PagoCuentaCobrarResource($pago)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(PagoCuentaCobrar $pagoCuentaCobrar): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new PagoCuentaCobrarResource($pagoCuentaCobrar)
        ]);
    }

    public function update(UpdatePagoCuentaCobrarRequest $request, PagoCuentaCobrar $pagoCuentaCobrar): JsonResponse
    {
        DB::beginTransaction();
        try {
            $montoAnterior = $pagoCuentaCobrar->monto_pago;
            $pagoCuentaCobrar->update($request->validated());

            // Recalcular saldo de cuenta por cobrar
            if ($request->filled('monto_pago') && $montoAnterior != $pagoCuentaCobrar->monto_pago) {
                $cuenta = CuentaPorCobrar::findOrFail($pagoCuentaCobrar->cuenta_por_cobrar_id);
                $cuenta->monto_pagado = $cuenta->monto_pagado - $montoAnterior + $pagoCuentaCobrar->monto_pago;
                $cuenta->saldo = $cuenta->monto_total - $cuenta->monto_pagado;
                $cuenta->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado exitosamente',
                'data' => new PagoCuentaCobrarResource($pagoCuentaCobrar)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PagoCuentaCobrar $pagoCuentaCobrar): JsonResponse
    {
        DB::beginTransaction();
        try {
            $pagoCuentaCobrar->update(['eliminado' => 1, 'activo' => 0]);

            // Restar el monto del pago de la cuenta por cobrar
            $cuenta = CuentaPorCobrar::findOrFail($pagoCuentaCobrar->cuenta_por_cobrar_id);
            $cuenta->monto_pagado -= $pagoCuentaCobrar->monto_pago;
            $cuenta->saldo = $cuenta->monto_total - $cuenta->monto_pagado;
            $cuenta->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    public function porCuenta(int $cuentaId): JsonResponse
    {
        $pagos = PagoCuentaCobrar::where('cuenta_por_cobrar_id', $cuentaId)
            ->where('eliminado', 0)
            ->orderBy('fecha_pago', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PagoCuentaCobrarResource::collection($pagos),
            'total_pagado' => $pagos->sum('monto_pago')
        ]);
    }

    public function porFormaPago(int $formaPagoId): JsonResponse
    {
        $pagos = PagoCuentaCobrar::where('forma_pago_id', $formaPagoId)
            ->where('eliminado', 0)
            ->orderBy('fecha_pago', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => PagoCuentaCobrarResource::collection($pagos),
            'total' => $pagos->sum('monto_pago')
        ]);
    }

    public function resumenPorFecha(Request $request): JsonResponse
    {
        $query = PagoCuentaCobrar::where('eliminado', 0);

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_pago', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_pago', '<=', $request->fecha_hasta);
        }

        $resumen = $query->selectRaw('count(*) as total_pagos, sum(monto_pago) as monto_total')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
