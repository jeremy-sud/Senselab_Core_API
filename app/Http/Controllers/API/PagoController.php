<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Controlador API para Pagos
 *
 * Gestiona los pagos de cuentas por cobrar y por pagar.
 * Actualiza automáticamente los saldos de las cuentas relacionadas.
 *
 * @package App\Http\Controllers\API
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PagoController extends Controller
{
    /**
     * Listar todos los pagos de la empresa autenticada
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $empresaId = $request->user()->empresa_id;
        
        $query = Pago::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra']);

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por forma de pago
        if ($request->filled('forma_pago_id')) {
            $query->where('forma_pago_id', $request->forma_pago_id);
        }

        // Filtro por proveedor
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        // Filtro por cliente
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Filtro por rango de fechas
        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
        }

        // Ordenamiento
        $query->orderBy($request->get('sort_by', 'fecha_pago'), $request->get('sort_order', 'desc'));

        $pagos = $query->paginate($request->get('per_page', 15));

        return PagoResource::collection($pagos);
    }

    /**
     * Crear un nuevo pago
     *
     * @param StorePagoRequest $request
     * @return JsonResponse
     */
    public function store(StorePagoRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $validated['empresa_id'] = $request->user()->empresa_id;

            $pago = Pago::create($validated);

            // Actualizar saldo de cuenta por pagar si aplica
            if ($pago->cuenta_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_pagar_id, $pago->monto);
            }

            // Actualizar saldo de cuenta por cobrar si aplica
            if ($pago->cuenta_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_cobrar_id, $pago->monto);
            }

            $pago->load(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'data' => new PagoResource($pago)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un pago específico
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new PagoResource($pago)
        ]);
    }

    /**
     * Actualizar un pago existente
     *
     * @param UpdatePagoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdatePagoRequest $request, int $id): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // No permitir modificar pagos ya procesados
        if ($pago->estado === 'Pagado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede modificar un pago que ya ha sido procesado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $montoAnterior = $pago->monto;
            $pago->update($request->validated());

            // Si cambió el monto, ajustar los saldos
            if ($request->filled('monto') && $request->monto != $montoAnterior) {
                $diferencia = $request->monto - $montoAnterior;

                if ($pago->cuenta_pagar_id) {
                    $this->actualizarCuentaPorPagar($pago->cuenta_pagar_id, $diferencia);
                }

                if ($pago->cuenta_cobrar_id) {
                    $this->actualizarCuentaPorCobrar($pago->cuenta_cobrar_id, $diferencia);
                }
            }

            $pago->load(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago actualizado exitosamente',
                'data' => new PagoResource($pago)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar (soft delete) un pago
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $pago = Pago::where('empresa_id', $empresaId)
            ->where('id', $id)
            ->where('eliminado', 0)
            ->firstOrFail();

        // No permitir eliminar pagos ya procesados
        if ($pago->estado === 'Pagado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un pago que ya ha sido procesado'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Revertir el monto en las cuentas
            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, -$pago->monto);
            }

            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, -$pago->monto);
            }

            $pago->update(['eliminado' => 1, 'activo' => 0]);

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

    /**
     * Actualizar el monto pagado en una cuenta por pagar
     *
     * @param int $cuentaId
     * @param float $monto
     * @return void
     */
    private function actualizarCuentaPorPagar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorPagar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        // Actualizar estado según saldo
        if ($cuenta->monto_pagado >= $cuenta->monto_original) {
            $cuenta->update(['estado' => 'Pagada Totalmente']);
        } elseif ($cuenta->monto_pagado > 0) {
            $cuenta->update(['estado' => 'Pagada Parcialmente']);
        }
    }

    /**
     * Actualizar el monto pagado en una cuenta por cobrar
     *
     * @param int $cuentaId
     * @param float $monto
     * @return void
     */
    private function actualizarCuentaPorCobrar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorCobrar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        // Actualizar estado según saldo
        if ($cuenta->monto_pagado >= $cuenta->monto_original) {
            $cuenta->update(['estado' => 'Pagada Totalmente']);
        } elseif ($cuenta->monto_pagado > 0) {
            $cuenta->update(['estado' => 'Pagada Parcialmente']);
        }
    }

    /**
     * Obtener resumen de pagos por forma de pago
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resumenPorFormaPago(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $resumen = Pago::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('estado', 'Pagado')
            ->select('forma_pago_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto) as total'))
            ->groupBy('forma_pago_id')
            ->with('formaPago')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resumen
        ]);
    }
}
