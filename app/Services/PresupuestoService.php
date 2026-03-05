<?php

namespace App\Services;

use App\Models\Presupuesto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

/**
 * Servicio para gestionar Presupuestos
 */
class PresupuestoService
{
    /**
     * Listar presupuestos con paginación
     *
     * @param int $empresaId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listar(int $empresaId, int $perPage = 15): LengthAwarePaginator
    {
        return Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->orderBy('periodo_inicio', 'desc')
            ->paginate($perPage);
    }

    /**
     * Crear un presupuesto
     *
     * @param array<string, mixed> $data
     * @return Presupuesto
     */
    public function crear(array $data): Presupuesto
    {
        return DB::transaction(function () use ($data) {
            $data['estado'] = $data['estado'] ?? 'Borrador';
            $presupuesto = Presupuesto::create($data);
            return $presupuesto->load('detalles.cuentaContable');
        });
    }

    /**
     * Obtener presupuesto por ID
     *
     * @param int $empresaId
     * @param int $id
     * @return Presupuesto
     */
    public function obtener(int $empresaId, int $id): Presupuesto
    {
        return Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->findOrFail($id);
    }

    /**
     * Actualizar presupuesto
     *
     * @param Presupuesto $presupuesto
     * @param array<string, mixed> $data
     * @return Presupuesto
     * @throws ValidationException
     */
    public function actualizar(Presupuesto $presupuesto, array $data): Presupuesto
    {
        if ($presupuesto->estado === 'Finalizado') {
            throw ValidationException::withMessages([
                'estado' => 'No se puede modificar un presupuesto finalizado.',
            ]);
        }

        return DB::transaction(function () use ($presupuesto, $data) {
            $presupuesto->update($data);
            return $presupuesto->fresh('detalles.cuentaContable') ?? $presupuesto;
        });
    }

    /**
     * Eliminar presupuesto
     *
     * @param Presupuesto $presupuesto
     * @return bool
     * @throws ValidationException
     */
    public function eliminar(Presupuesto $presupuesto): bool
    {
        if ($presupuesto->estado === 'Activo') {
            throw ValidationException::withMessages([
                'estado' => 'No se puede eliminar un presupuesto activo.',
            ]);
        }

        $presupuesto->delete();
        return true;
    }

    /**
     * Activar presupuesto
     *
     * @param Presupuesto $presupuesto
     * @return Presupuesto
     * @throws ValidationException
     */
    public function activar(Presupuesto $presupuesto): Presupuesto
    {
        if ($presupuesto->estado === 'Activo') {
            throw ValidationException::withMessages([
                'estado' => 'El presupuesto ya está activo.',
            ]);
        }

        if ($presupuesto->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'detalles' => 'El presupuesto debe tener al menos un detalle para activarse.',
            ]);
        }

        $presupuesto->update(['estado' => 'Activo']);
        return $presupuesto->fresh('detalles') ?? $presupuesto;
    }

    /**
     * Finalizar presupuesto
     *
     * @param Presupuesto $presupuesto
     * @return Presupuesto
     * @throws ValidationException
     */
    public function finalizar(Presupuesto $presupuesto): Presupuesto
    {
        if ($presupuesto->estado === 'Finalizado') {
            throw ValidationException::withMessages([
                'estado' => 'El presupuesto ya está finalizado.',
            ]);
        }

        $presupuesto->update(['estado' => 'Finalizado']);
        return $presupuesto->fresh('detalles') ?? $presupuesto;
    }

    /**
     * Obtener presupuestos activos
     *
     * @param int $empresaId
     * @return Collection
     */
    public function activos(int $empresaId): Collection
    {
        return Presupuesto::where('empresa_id', $empresaId)
            ->where('estado', 'Activo')
            ->with('detalles')
            ->orderBy('periodo_inicio', 'desc')
            ->get();
    }

    /**
     * Obtener resumen de un presupuesto
     *
     * @param int $empresaId
     * @param int $id
     * @return array<string, mixed>
     */
    public function resumen(int $empresaId, int $id): array
    {
        $presupuesto = Presupuesto::where('empresa_id', $empresaId)
            ->with('detalles.cuentaContable')
            ->findOrFail($id);

        return [
            'presupuesto' => $presupuesto,
            'total_presupuestado' => $presupuesto->detalles->sum('monto_presupuestado'),
            'cantidad_lineas' => $presupuesto->detalles->count(),
            'duracion_dias' => $presupuesto->duracionDias(),
        ];
    }
}
