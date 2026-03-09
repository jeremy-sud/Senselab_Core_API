<?php

namespace App\Services;

use App\Models\CuentaPorCobrar;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestionar Cuentas por Cobrar
 */
class CuentaPorCobrarService
{
    /**
     * Listar cuentas por cobrar con filtros
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(int $empresaId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['cliente', 'venta', 'empresa']);

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
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
     * Crear una cuenta por cobrar
     *
     * @param array<string, mixed> $data
     * @return CuentaPorCobrar
     */
    public function crear(array $data): CuentaPorCobrar
    {
        $data['monto_pendiente'] = $data['monto_original'] - ($data['monto_pagado'] ?? 0);

        $cuenta = CuentaPorCobrar::create($data);
        $cuenta->load(['cliente', 'venta', 'empresa']);
        return $cuenta;
    }

    /**
     * Obtener cuenta por cobrar por ID
     *
     * @param int $empresaId
     * @param int $id
     * @return CuentaPorCobrar
     */
    public function obtener(int $empresaId, int $id): CuentaPorCobrar
    {
        return CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['cliente', 'venta', 'empresa'])
            ->findOrFail($id);
    }

    /**
     * Actualizar cuenta por cobrar
     *
     * @param CuentaPorCobrar $cuenta
     * @param array<string, mixed> $data
     * @return CuentaPorCobrar
     */
    public function actualizar(CuentaPorCobrar $cuenta, array $data): CuentaPorCobrar
    {
        $cuenta->update($data);
        $cuenta->load(['cliente', 'venta', 'empresa']);
        return $cuenta;
    }

    /**
     * Eliminar cuenta por cobrar (soft delete)
     *
     * @param CuentaPorCobrar $cuenta
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(CuentaPorCobrar $cuenta): bool
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
        $cuentas = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('estado', 'Vencida')
            ->with(['cliente'])
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
        $resumen = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->selectRaw('estado, COUNT(*) as cantidad, SUM(monto_original) as total, SUM(monto_pagado) as pagado')
            ->groupBy('estado')
            ->get();

        $totalPendiente = CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->whereIn('estado', ['Pendiente', 'Pagada Parcialmente', 'Vencida'])
            ->sum('monto_pendiente');

        return [
            'por_estado' => $resumen,
            'total_pendiente' => (float) $totalPendiente,
        ];
    }
}
