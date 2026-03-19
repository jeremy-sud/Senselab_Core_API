<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\BusUnidad;
use App\Models\HorarioRuta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<BusUnidad> */
class BusUnidadService extends BaseService
{
    protected string $modelClass = BusUnidad::class;

    protected array $searchFields = ['placa', 'identificador_interno'];

    protected array $defaultRelations = ['empresa', 'modelo'];

    /**
     * @param Builder<BusUnidad> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('eliminado', 0);

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', $filtros['activo']);
        }

        if (!empty($filtros['modelo_id'])) {
            $query->where('modelo_id', $filtros['modelo_id']);
        }
    }

    public function obtener(int $id): Model
    {
        /** @var BusUnidad */
        return BusUnidad::where('eliminado', 0)
            ->with(['empresa', 'modelo'])
            ->findOrFail($id);
    }

    public function obtenerPorEmpresa(int $id, int $empresaId): BusUnidad
    {
        /** @var BusUnidad */
        return BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'modelo', 'horariosRuta'])
            ->findOrFail($id);
    }

    public function eliminar(Model $model): bool
    {
        /** @var BusUnidad $model */
        if (HorarioRuta::where('bus_id', $model->id)->where('estado', '!=', 'Finalizado')->exists()) {
            throw new BusinessException('No se puede eliminar un bus con horarios de ruta activos');
        }

        $model->update(['eliminado' => 1, 'activo' => 0]);

        return true;
    }

    /**
     * Buses activos sin viajes en curso.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BusUnidad>
     */
    public function disponibles(int $empresaId): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, BusUnidad> */
        return BusUnidad::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->with(['modelo'])
            ->whereNotIn('id', function ($query) {
                $query->select('bus_id')
                    ->from('horarios_ruta')
                    ->where('estado', 'En Viaje');
            })
            ->get();
    }

    /**
     * Resumen de la flota de la empresa.
     *
     * @return array{total_buses: int, buses_activos: int, buses_en_viaje: int, capacidad_total: int}
     */
    public function resumenFlota(int $empresaId): array
    {
        $base = BusUnidad::where('empresa_id', $empresaId)->where('eliminado', 0);

        return [
            'total_buses' => (clone $base)->count(),
            'buses_activos' => (clone $base)->where('activo', 1)->count(),
            'buses_en_viaje' => (clone $base)->whereIn('id', function ($q) {
                $q->select('bus_id')->from('horarios_ruta')->where('estado', 'En Viaje');
            })->count(),
            'capacidad_total' => (int) (clone $base)->where('activo', 1)->sum('capacidad_asientos'),
        ];
    }

    /**
     * Buses de un modelo específico.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, BusUnidad>
     */
    public function porModelo(int $empresaId, int $modeloId): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, BusUnidad> */
        return BusUnidad::where('empresa_id', $empresaId)
            ->where('modelo_id', $modeloId)
            ->where('eliminado', 0)
            ->with(['modelo'])
            ->get();
    }
}
