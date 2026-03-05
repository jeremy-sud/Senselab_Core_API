<?php

namespace App\Services;

use App\Models\CuentaPorPagar;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestionar Cuentas por Pagar
 */
class CuentaPorPagarService
{
    /**
     * Listar cuentas por pagar con filtros
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(int $empresaId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['proveedor', 'ordenCompra', 'empresa']);

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (!empty($filtros['vencidas'])) {
            $query->where('estado', 'Vencida');
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_emision', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_emision', '<=', $filtros['fecha_hasta']);
        }

        $sortBy = $filtros['sort_by'] ?? 'fecha_vencimiento';
        $sortOrder = $filtros['sort_order'] ?? 'asc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Crear una cuenta por pagar
     *
     * @param array<string, mixed> $data
     * @return CuentaPorPagar
     */
    public function crear(array $data): CuentaPorPagar
    {
        if (empty($data['fecha_recepcion_documento'])) {
            $data['fecha_recepcion_documento'] = now()->toDateString();
        }

        $cuenta = CuentaPorPagar::create($data);
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);
        return $cuenta;
    }

    /**
     * Obtener cuenta por pagar por ID
     *
     * @param int $empresaId
     * @param int $id
     * @return CuentaPorPagar
     */
    public function obtener(int $empresaId, int $id): CuentaPorPagar
    {
        return CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['proveedor', 'ordenCompra', 'empresa'])
            ->findOrFail($id);
    }

    /**
     * Actualizar cuenta por pagar
     *
     * @param CuentaPorPagar $cuenta
     * @param array<string, mixed> $data
     * @return CuentaPorPagar
     */
    public function actualizar(CuentaPorPagar $cuenta, array $data): CuentaPorPagar
    {
        $cuenta->update($data);
        $cuenta->load(['proveedor', 'ordenCompra', 'empresa']);
        return $cuenta;
    }

    /**
     * Eliminar cuenta por pagar (soft delete)
     *
     * @param CuentaPorPagar $cuenta
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(CuentaPorPagar $cuenta): bool
    {
        if ($cuenta->monto_pagado > 0) {
            throw ValidationException::withMessages([
                'cuenta' => 'No se puede eliminar una cuenta con pagos registrados.',
            ]);
        }

        $cuenta->update(['eliminado' => 1, 'activo' => 0]);
        return true;
    }

    /**
     * Obtener cuentas vencidas con resumen
     *
     * @param int $empresaId
     * @return array<string, mixed>
     */
    public function vencidas(int $empresaId): array
    {
        $cuentas = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('estado', 'Vencida')
            ->with(['proveedor'])
            ->get();

        return [
            'total_vencido' => $cuentas->sum('monto_pendiente'),
            'cantidad_vencidas' => $cuentas->count(),
            'cuentas' => $cuentas,
        ];
    }

    /**
     * Obtener resumen por estado
     *
     * @param int $empresaId
     * @return array<string, mixed>
     */
    public function resumen(int $empresaId): array
    {
        $resumen = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->selectRaw('estado, COUNT(*) as cantidad, SUM(monto_original) as total, SUM(monto_pagado) as pagado')
            ->groupBy('estado')
            ->get();

        $totalPendiente = CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente', 'Vencida'])
            ->sum('monto_pendiente');

        return [
            'por_estado' => $resumen,
            'total_pendiente' => (float) $totalPendiente,
        ];
    }
}
