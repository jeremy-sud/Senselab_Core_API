<?php

namespace App\Services;

use App\Models\AsientoContable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar Asientos Contables
 *
 * Encapsula la lógica de negocio para asientos contables
 * Fecha de creación: 12 de febrero de 2026
 * Refactorizado: 13 de febrero de 2026
 */
class AsientoContableService
{
    /**
     * Crear un nuevo asiento contable
     *
     * @param array<string, mixed> $data
     */
    public function crear(array $data): AsientoContable
    {
        return DB::transaction(function () use ($data) {
            // Calcular totales de debe y haber
            $detalles = $data['detalles'] ?? [];
            $totalDebe = array_sum(array_map(fn($d) => (float)($d['debe'] ?? 0), $detalles));
            $totalHaber = array_sum(array_map(fn($d) => (float)($d['haber'] ?? 0), $detalles));

            $data['total_debe'] = $totalDebe;
            $data['total_haber'] = $totalHaber;
            $data['estado'] = $data['estado'] ?? 'Borrador';

            if (empty($data['numero_asiento'])) {
                $data['numero_asiento'] = $this->generarNumeroAsiento($data['empresa_id'] ?? null);
            }

            if (empty($data['usuario_id']) && Auth::check()) {
                $data['usuario_id'] = Auth::id();
            }

            $asiento = AsientoContable::create($data);

            foreach ($detalles as $detalle) {
                $asiento->detalles()->create($detalle);
            }

            return $asiento->load(['detalles.cuentaContable', 'empresa']);
        });
    }

    /**
     * Obtener asiento por ID con relaciones
     */
    public function obtener(int $asientoId): ?AsientoContable
    {
        return AsientoContable::with(['detalles.cuentaContable', 'empresa'])
            ->where('eliminado', 0)
            ->find($asientoId);
    }

    /**
     * Listar asientos con paginación
     */
    public function listar(int $perPage = 15): LengthAwarePaginator
    {
        return AsientoContable::with(['detalles.cuentaContable', 'empresa'])
            ->where('eliminado', 0)
            ->orderByDesc('fecha_asiento')
            ->paginate($perPage);
    }

    /**
     * Asientos por estado
     */
    public function porEstado(string $estado, int $perPage = 15): LengthAwarePaginator
    {
        return AsientoContable::where('estado', $estado)
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable'])
            ->orderByDesc('fecha_asiento')
            ->paginate($perPage);
    }

    /**
     * Asientos por cuenta contable
     */
    public function porCuenta(int $cuentaContableId, int $perPage = 15): LengthAwarePaginator
    {
        return AsientoContable::whereHas('detalles', function ($q) use ($cuentaContableId) {
                $q->where('cuenta_contable_id', $cuentaContableId);
            })
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable'])
            ->orderByDesc('fecha_asiento')
            ->paginate($perPage);
    }

    /**
     * Asientos entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): LengthAwarePaginator
    {
        return AsientoContable::whereBetween('fecha_asiento', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->where('eliminado', 0)
            ->with(['detalles.cuentaContable'])
            ->orderByDesc('fecha_asiento')
            ->paginate($perPage);
    }

    /**
     * Actualizar asiento
     *
     * @param AsientoContable $asiento
     * @param array<string, mixed> $data
     */
    public function actualizar(AsientoContable $asiento, array $data): AsientoContable
    {
        return DB::transaction(function () use ($asiento, $data) {
            // Si se actualizan los detalles, recalcular totales
            if (isset($data['detalles'])) {
                $detalles = $data['detalles'];
                $data['total_debe'] = array_sum(array_map(fn($d) => (float)($d['debe'] ?? 0), $detalles));
                $data['total_haber'] = array_sum(array_map(fn($d) => (float)($d['haber'] ?? 0), $detalles));

                // Eliminar detalles antiguos y crear nuevos
                $asiento->detalles()->delete();
                foreach ($detalles as $detalle) {
                    $asiento->detalles()->create($detalle);
                }
            }

            $asiento->update($data);
            return $asiento->fresh(['detalles.cuentaContable', 'empresa']) ?? $asiento;
        });
    }

    /**
     * Eliminar asiento (soft delete)
     */
    public function eliminar(AsientoContable $asiento): bool
    {
        $asiento->update(['eliminado' => 1, 'activo' => 0]);
        $asiento->detalles()->update(['eliminado' => 1]);
        return true;
    }

    /**
     * Validar que el asiento esté balanceado
     */
    public function validarBalanceo(AsientoContable $asiento): bool
    {
        return abs($asiento->total_debe - $asiento->total_haber) < 0.01;
    }

    /**
     * Generar número de asiento correlativo por empresa y año.
     */
    private function generarNumeroAsiento(?int $empresaId): string
    {
        $year = now()->year;
        $prefix = "ASI-{$year}-";

        $last = AsientoContable::withoutGlobalScopes()
            ->when($empresaId, fn($q) => $q->where('empresa_id', $empresaId))
            ->where('numero_asiento', 'like', $prefix . '%')
            ->orderByDesc('numero_asiento')
            ->value('numero_asiento');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
