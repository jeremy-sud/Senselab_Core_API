<?php

namespace App\Http\Controllers;

use App\Models\PagoCuentaCobrar;
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
        $query = PagoCuentaCobrar::where('eliminado', 0);

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

            // Actualizar saldo de la cuenta por cobrar
            $this->actualizarSaldoCuenta($pago->cuenta_por_cobrar_id);

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
            $pagoCuentaCobrar->update($request->validated());

            $this->actualizarSaldoCuenta($pagoCuentaCobrar->cuenta_por_cobrar_id);

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

            $this->actualizarSaldoCuenta($pagoCuentaCobrar->cuenta_por_cobrar_id);

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

    public function reportePorFecha(Request $request): JsonResponse
    {
        $query = PagoCuentaCobrar::where('eliminado', 0);

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_pago', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_pago', '<=', $request->fecha_hasta);
        }

        $pagos = $query->get();

        return response()->json([
            'success' => true,
            'data' => PagoCuentaCobrarResource::collection($pagos),
            'resumen' => [
                'total_pagos' => $pagos->count(),
                'monto_total' => $pagos->sum('monto_pago'),
                'por_moneda' => $pagos->groupBy('moneda')->map(function ($grupo) {
                    return [
                        'cantidad' => $grupo->count(),
                        'total' => $grupo->sum('monto_pago')
                    ];
                })
            ]
        ]);
    }

    private function actualizarSaldoCuenta(int $cuentaId): void
    {
        $totalPagos = PagoCuentaCobrar::where('cuenta_por_cobrar_id', $cuentaId)
            ->where('eliminado', 0)
            ->sum('monto_pago');

        $cuenta = \App\Models\CuentaPorCobrar::find($cuentaId);
        if ($cuenta) {
            $cuenta->update(['monto_pagado' => $totalPagos]);
        }
    }
}
