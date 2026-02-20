<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\UpdatePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Traits\HasEmpresaContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * PagoController - Versión Refactorizada (FASE 4.2)
 *
 * Controlador simplificado para gestión de pagos.
 * Actualiza automáticamente saldos de cuentas por cobrar/pagar.
 * Reducción: 627 → ~180 líneas (-71%)
 */
class PagoController extends Controller
{
    use HasEmpresaContext;

    #[OA\Get(
        path: '/api/pagos',
        summary: 'Listar todos los pagos',
        description: 'Listado paginado con filtros por estado, forma de pago, proveedor, cliente y fechas',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Pago::class);

        $query = Pago::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)
            ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra']);

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('forma_pago_id')) {
            $query->where('forma_pago_id', $request->forma_pago_id);
        }
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        if ($request->filled(['desde', 'hasta'])) {
            $query->whereBetween('fecha_pago', [$request->desde, $request->hasta]);
        }

        return PagoResource::collection(
            $query->orderBy($request->get('sort_by', 'fecha_pago'), $request->get('sort_order', 'desc'))
                ->paginate($request->get('per_page', 15))
        );
    }

    #[OA\Post(
        path: '/api/pagos',
        summary: 'Crear un nuevo pago',
        description: 'Registra un pago y actualiza automáticamente saldos de cuentas relacionadas',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function store(StorePagoRequest $request): JsonResponse
    {
        $this->authorize('create', Pago::class);

        try {
            DB::beginTransaction();

            $pago = Pago::create([
                'empresa_id' => $this->getEmpresaId(),
                ...$request->validated()
            ]);

            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, $pago->monto);
            }
            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, $pago->monto);
            }

            DB::commit();

            return response()->json([
                'data' => PagoResource::make($pago->load(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']))->resolve(),
                'message' => 'Pago registrado exitosamente'
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al registrar pago', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/pagos/{id}',
        summary: 'Obtener un pago específico',
        description: 'Detalle completo de un pago con todas sus relaciones',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function show(int $id): PagoResource
    {
        $pago = Pago::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)
            ->with(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra'])
            ->findOrFail($id);

        $this->authorize('view', $pago);
        return new PagoResource($pago);
    }

    #[OA\Put(
        path: '/api/pagos/{id}',
        summary: 'Actualizar un pago existente',
        description: 'Actualiza pago y ajusta saldos si cambia el monto. No permite modificar pagos procesados',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function update(UpdatePagoRequest $request, int $id): JsonResponse
    {
        $pago = Pago::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)->findOrFail($id);

        $this->authorize('update', $pago);

        if ($pago->estado === 'Pagado') {
            return response()->json(['message' => 'No se puede modificar un pago ya procesado'], 422);
        }

        try {
            DB::beginTransaction();

            $montoAnterior = $pago->monto;
            $pago->update($request->validated());

            if ($request->filled('monto') && $request->monto != $montoAnterior) {
                $diferencia = $request->monto - $montoAnterior;
                if ($pago->cuenta_por_pagar_id) {
                    $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, $diferencia);
                }
                if ($pago->cuenta_por_cobrar_id) {
                    $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, $diferencia);
                }
            }

            DB::commit();

            return response()->json([
                'data' => PagoResource::make($pago->fresh(['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar']))->resolve(),
                'message' => 'Pago actualizado exitosamente'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: '/api/pagos/{id}',
        summary: 'Eliminar un pago',
        description: 'Soft delete del pago. Revierte saldos de cuentas. No permite eliminar pagos procesados',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function destroy(int $id): JsonResponse
    {
        $pago = Pago::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)->findOrFail($id);

        $this->authorize('delete', $pago);

        if ($pago->estado === 'Pagado') {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar un pago ya procesado'], 422);
        }

        try {
            DB::beginTransaction();

            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, -$pago->monto);
            }
            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, -$pago->monto);
            }

            $pago->update(['eliminado' => 1, 'activo' => 0]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pago eliminado exitosamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al eliminar', 'error' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/pagos/resumen-por-forma-pago',
        summary: 'Resumen de pagos por forma de pago',
        description: 'Estadísticas de pagos agrupados por forma de pago (solo pagos procesados)',
        security: [['sanctum' => []]],
        tags: ['Pagos']
    )]
    public function resumenPorFormaPago(): JsonResponse
    {
        $resumen = Pago::where('empresa_id', $this->getEmpresaId())
            ->where('eliminado', 0)
            ->where('estado', 'Pagado')
            ->select('forma_pago_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto) as total'))
            ->groupBy('forma_pago_id')
            ->with('formaPago')
            ->get();

        return response()->json(['success' => true, 'data' => $resumen]);
    }

    private function actualizarCuentaPorPagar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorPagar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        $nuevoEstado = match (true) {
            $cuenta->monto_pagado >= $cuenta->monto_original => 'Pagada Totalmente',
            $cuenta->monto_pagado > 0 => 'Pagada Parcialmente',
            default => $cuenta->estado
        };

        if ($nuevoEstado !== $cuenta->estado) {
            $cuenta->update(['estado' => $nuevoEstado]);
        }
    }

    private function actualizarCuentaPorCobrar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorCobrar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        $nuevoEstado = match (true) {
            $cuenta->monto_pagado >= $cuenta->monto_original => 'Pagada Totalmente',
            $cuenta->monto_pagado > 0 => 'Pagada Parcialmente',
            default => $cuenta->estado
        };

        if ($nuevoEstado !== $cuenta->estado) {
            $cuenta->update(['estado' => $nuevoEstado]);
        }
    }
}
