<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaPorCobrarRequest;
use App\Http\Requests\UpdateCuentaPorCobrarRequest;
use App\Http\Resources\CuentaPorCobrarResource;
use App\Models\CuentaPorCobrar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Cuentas por Cobrar
 *
 * Gestiona las cuentas por cobrar de la empresa, generadas por ventas a crédito.
 * Incluye control de saldos, vencimientos y estados de pago.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorCobrarController extends Controller
{
    /**
     * Listar todas las cuentas por cobrar de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['cliente', 'venta', 'empresa']);

        // Filtros opcionales
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('vencidas')) {
            $query->where('fecha_vencimiento', '<', now())
                ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente']);
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_emision', [$request->desde, $request->hasta]);
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'fecha_vencimiento'), $request->get('sort_order', 'asc'));

        $cuentas = $query->paginate($request->get('per_page', 15));

        return CuentaPorCobrarResource::collection($cuentas);
    }

    /**
     * Crear una nueva cuenta por cobrar
     *
     * @param StoreCuentaPorCobrarRequest $request
     * @return JsonResponse
     */
    public function store(StoreCuentaPorCobrarRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = $request->user()->empresa_id;

        $cuenta = CuentaPorCobrar::create($validated);
        $cuenta->load(['cliente', 'venta', 'empresa']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por cobrar creada exitosamente',
            'data' => new CuentaPorCobrarResource($cuenta)
        ], 201);
    }

    /**
     * Mostrar una cuenta por cobrar específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['cliente', 'venta', 'empresa'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CuentaPorCobrarResource($cuenta)
        ]);
    }

    /**
     * Actualizar una cuenta por cobrar existente
     *
     * @param UpdateCuentaPorCobrarRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCuentaPorCobrarRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $cuenta->update($request->validated());
        $cuenta->load(['cliente', 'venta', 'empresa']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por cobrar actualizada exitosamente',
            'data' => new CuentaPorCobrarResource($cuenta)
        ]);
    }

    /**
     * Eliminar (soft delete) una cuenta por cobrar
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no tenga pagos registrados
        if ($cuenta->monto_pagado > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta por cobrar que ya tiene pagos registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por cobrar eliminada exitosamente'
        ]);
    }

    /**
     * Obtener resumen de cuentas por cobrar vencidas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vencidas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $vencidas = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('fecha_vencimiento', '<', now())
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente'])
            ->with(['cliente'])
            ->get();

        $totalVencido = $vencidas->sum('saldo_pendiente');
        $cantidadVencidas = $vencidas->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_vencido' => $totalVencido,
                'cantidad_vencidas' => $cantidadVencidas,
                'cuentas' => CuentaPorCobrarResource::collection($vencidas)
            ]
        ]);
    }

    /**
     * Obtener resumen de cuentas por cobrar por estado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resumen(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(saldo_pendiente) as total_saldo'))
            ->groupBy('estado')
            ->get();

        $totalGeneral = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->sum('saldo_pendiente');

        return response()->json([
            'success' => true,
            'data' => [
                'por_estado' => $resumen,
                'total_general' => $totalGeneral
            ]
        ]);
    }
}
