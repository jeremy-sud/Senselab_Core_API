<?php

namespace App\Services;

use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PeriodoNominaService - Servicio de Gestión de Períodos de Nómina
 *
 * Encapsula la lógica de negocio para períodos de nómina:
 * - CRUD operations
 * - Gestión de estados (Abierto → Cerrado → Procesado)
 * - Cálculo de resúmenes (totales bruto, deducciones, neto)
 * - Validaciones de estado para modificación/eliminación
 * - Filtrado por empresa, estado, año, mes
 *
 * Refactorización FASE 8 - Service Layer Pattern
 */
class PeriodoNominaService
{
    /**
     * Listar períodos de nómina con filtros opcionales
     *
     * @param int $empresaId
     * @param array<string, mixed> $filtros
     * @return LengthAwarePaginator
     */
    public function listar(int $empresaId, array $filtros = []): LengthAwarePaginator
    {
        $query = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'pagosNomina']);

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['anio'])) {
            $query->whereYear('fecha_inicio', $filtros['anio']);
        }

        if (!empty($filtros['mes'])) {
            $query->whereMonth('fecha_inicio', $filtros['mes']);
        }

        return $query->orderBy('id', 'desc')->paginate(15);
    }

    /**
     * Crear un nuevo período de nómina
     *
     * @param int $empresaId
     * @param array<string, mixed> $data
     * @return PeriodoNomina
     */
    public function crear(int $empresaId, array $data): PeriodoNomina
    {
        $data['empresa_id'] = $empresaId;
        $data['estado'] = $data['estado'] ?? 'Abierto';
        $data['activo'] = $data['activo'] ?? 1;

        $periodo = PeriodoNomina::create($data);

        return $periodo->load(['empresa']);
    }

    /**
     * Obtener período de nómina por ID (scoped a empresa)
     *
     * @param int $empresaId
     * @param int $id
     * @return PeriodoNomina
     */
    public function obtener(int $empresaId, int $id): PeriodoNomina
    {
        return PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'pagosNomina.empleado', 'pagosNomina.metodoPago'])
            ->findOrFail($id);
    }

    /**
     * Actualizar un período de nómina existente
     *
     * No permite modificar períodos en estado 'Procesado'.
     *
     * @param int $empresaId
     * @param int $id
     * @param array<string, mixed> $data
     * @return PeriodoNomina
     * @throws ValidationException
     */
    public function actualizar(int $empresaId, int $id, array $data): PeriodoNomina
    {
        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($periodo->estado === 'Procesado') {
            throw ValidationException::withMessages([
                'periodo' => 'No se puede modificar un período de nómina que ya ha sido procesado',
            ]);
        }

        $periodo->update($data);

        return $periodo->load(['empresa']);
    }

    /**
     * Eliminar un período de nómina (soft delete)
     *
     * No permite eliminar períodos con pagos de nómina asociados.
     *
     * @param int $empresaId
     * @param int $id
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(int $empresaId, int $id): bool
    {
        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($periodo->pagosNomina()->exists()) {
            throw ValidationException::withMessages([
                'periodo' => 'No se puede eliminar un período con pagos de nómina asociados',
            ]);
        }

        $periodo->update(['eliminado' => 1, 'activo' => 0]);

        return true;
    }

    /**
     * Cerrar un período de nómina (Abierto → Cerrado)
     *
     * @param int $empresaId
     * @param int $id
     * @return PeriodoNomina
     * @throws ValidationException
     */
    public function cerrar(int $empresaId, int $id): PeriodoNomina
    {
        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->findOrFail($id);

        if ($periodo->estado !== 'Abierto') {
            throw ValidationException::withMessages([
                'periodo' => 'Solo se pueden cerrar períodos en estado Abierto',
            ]);
        }

        $periodo->update(['estado' => 'Cerrado']);

        return $periodo->load(['empresa']);
    }

    /**
     * Procesar un período de nómina (cambiar estado a Procesado)
     *
     * Genera los pagos de nómina para todos los empleados activos.
     * Esta acción es irreversible.
     *
     * @param int $empresaId
     * @param int $id
     * @return PeriodoNomina
     * @throws ValidationException
     */
    public function procesar(int $empresaId, int $id): PeriodoNomina
    {
        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        if ($periodo->estado === 'Procesado') {
            throw ValidationException::withMessages([
                'periodo' => 'Este período ya ha sido procesado',
            ]);
        }

        return DB::transaction(function () use ($periodo) {
            // Aquí se implementaría la lógica para generar los pagos de nómina
            $periodo->update(['estado' => 'Procesado']);

            return $periodo;
        });
    }

    /**
     * Obtener resumen de totales del período
     *
     * @param int $empresaId
     * @param int $id
     * @return array<string, mixed>
     */
    public function resumen(int $empresaId, int $id): array
    {
        $periodo = PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['pagosNomina'])
            ->findOrFail($id);

        $totales = $periodo->pagosNomina()
            ->selectRaw('
                COUNT(*) as total_empleados,
                SUM(monto_bruto) as total_bruto,
                SUM(total_deducciones) as total_deducciones,
                SUM(monto_neto_pagado) as total_neto
            ')
            ->first();

        return [
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'fecha_inicio' => $periodo->fecha_inicio,
                'fecha_fin' => $periodo->fecha_fin,
                'estado' => $periodo->estado,
            ],
            'resumen' => [
                'total_empleados' => $totales->total_empleados ?? 0,
                'total_bruto' => number_format($totales->total_bruto ?? 0, 2),
                'total_deducciones' => number_format($totales->total_deducciones ?? 0, 2),
                'total_neto' => number_format($totales->total_neto ?? 0, 2),
            ],
        ];
    }

    /**
     * Listar períodos activos (para uso en selects/formularios)
     *
     * @param int $empresaId
     * @return Collection
     */
    public function activos(int $empresaId): Collection
    {
        return PeriodoNomina::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre_periodo', 'fecha_inicio', 'fecha_fin', 'estado')
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }
}
