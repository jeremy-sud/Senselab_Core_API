<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuentaPorPagarRequest;
use App\Http\Requests\UpdateCuentaPorPagarRequest;
use App\Http\Resources\CuentaPorPagarResource;
use App\Models\CuentaPorPagar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Cuentas por Pagar
 *
 * Gestiona las cuentas por pagar de la empresa a proveedores y acreedores.
 * Incluye control de saldos, vencimientos y estados de pago.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorPagarController extends Controller
{
    /**
     * Listar todas las cuentas por pagar de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['proveedor', 'ordenCompra', 'empresa']);

        // Filtros opcionales
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
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

        return CuentaPorPagarResource::collection($cuentas);
    }

    /**
     * Crear una nueva cuenta por pagar
     *
     * @param StoreCuentaPorPagarRequest $request
     * @return JsonResponse
     */
    public function store(StoreCuentaPorPagarRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['empresa_id'] = $request->user()->empresa_id;

        // Fecha de recepción por defecto es hoy si no se proporciona
        if (!isset($validated['fecha_recepcion_documento'])) {
            $validated['fecha_recepcion_documento'] = now()->toDateString();
        }

        $cuenta = CuentaPorPagar::create($validated);
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar creada exitosamente',
            'data' => new CuentaPorPagarResource($cuenta)
        ], 201);
    }

    /**
     * Mostrar una cuenta por pagar específica
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['proveedor', 'ordenCompra', 'empresa'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CuentaPorPagarResource($cuenta)
        ]);
    }

    /**
     * Actualizar una cuenta por pagar existente
     *
     * @param UpdateCuentaPorPagarRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCuentaPorPagarRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        $cuenta->update($request->validated());
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar actualizada exitosamente',
            'data' => new CuentaPorPagarResource($cuenta)
        ]);
    }

    /**
     * Eliminar (soft delete) una cuenta por pagar
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // Validar que no tenga pagos registrados
        if ($cuenta->monto_pagado > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una cuenta por pagar que ya tiene pagos registrados'
            ], 422);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Cuenta por pagar eliminada exitosamente'
        ]);
    }

    /**
     * Obtener resumen de cuentas por pagar vencidas
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vencidas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $vencidas = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('fecha_vencimiento', '<', now())
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente'])
            ->with(['proveedor'])
            ->get();

        $totalVencido = $vencidas->sum('monto_pendiente');
        $cantidadVencidas = $vencidas->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_vencido' => $totalVencido,
                'cantidad_vencidas' => $cantidadVencidas,
                'cuentas' => CuentaPorPagarResource::collection($vencidas)
            ]
        ]);
    }

    /**
     * Obtener resumen de cuentas por pagar por estado
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resumen(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->select('estado', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_pendiente) as total_saldo'))
            ->groupBy('estado')
            ->get();

        $totalGeneral = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->sum('monto_pendiente');

        return response()->json([
            'success' => true,
            'data' => [
                'por_estado' => $resumen,
                'total_general' => $totalGeneral
            ]
        ]);
    }
}
