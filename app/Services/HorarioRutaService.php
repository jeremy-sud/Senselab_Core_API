<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\BusUnidad;
use App\Models\HorarioRuta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<HorarioRuta> */
class HorarioRutaService extends BaseService
{
    protected string $modelClass = HorarioRuta::class;

    protected array $defaultRelations = ['ruta', 'bus'];

    protected string $defaultOrderBy = 'fecha_salida';

    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<HorarioRuta> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('eliminado', 0);

        if (!empty($filtros['ruta_id'])) {
            $query->where('ruta_id', $filtros['ruta_id']);
        }

        if (!empty($filtros['bus_id'])) {
            $query->where('bus_id', $filtros['bus_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha'])) {
            $query->whereDate('fecha_salida', $filtros['fecha']);
        }

        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $query->whereBetween('fecha_salida', [$filtros['desde'], $filtros['hasta']]);
        }
    }

    /**
     * @param Builder<HorarioRuta> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyOrdering(Builder $query, array $filtros): void
    {
        $query->orderByDesc('fecha_salida')->orderByDesc('hora_salida');
    }

    public function obtener(int $id): Model
    {
        /** @var HorarioRuta */
        return HorarioRuta::where('eliminado', 0)
            ->with(['ruta', 'bus', 'tiquetesDetalle'])
            ->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return HorarioRuta
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $bus = BusUnidad::findOrFail($data['bus_id']);

            $data['asientos_disponibles'] = $bus->capacidad_asientos;
            $data['estado'] = $data['estado'] ?? 'Programado';
            $data['activo'] = 1;

            /** @var HorarioRuta $horario */
            $horario = HorarioRuta::create($data);

            return $horario->load(['ruta', 'bus']);
        });
    }

    /**
     * @param HorarioRuta $model
     * @param array<string, mixed> $data
     * @return HorarioRuta
     */
    public function actualizar(Model $model, array $data): Model
    {
        /** @var HorarioRuta $model */
        if (in_array($model->estado, ['En Viaje', 'Finalizado'])) {
            throw new BusinessException('No se puede modificar un horario En Viaje o Finalizado');
        }

        $model->update($data);

        /** @var HorarioRuta */
        return $model->fresh(['ruta', 'bus']);
    }

    public function eliminar(Model $model): bool
    {
        /** @var HorarioRuta $model */
        if ($model->tiquetesDetalle()->exists()) {
            throw new BusinessException('No se puede eliminar un horario con tiquetes vendidos');
        }

        $model->update(['eliminado' => 1, 'activo' => 0]);

        return true;
    }

    public function iniciarViaje(HorarioRuta $horario): HorarioRuta
    {
        if ($horario->estado !== 'Programado') {
            throw new BusinessException('Solo se pueden iniciar viajes Programados');
        }

        $horario->update(['estado' => 'En Viaje']);

        /** @var HorarioRuta */
        return $horario->fresh(['ruta', 'bus']);
    }

    public function finalizarViaje(HorarioRuta $horario): HorarioRuta
    {
        if ($horario->estado !== 'En Viaje') {
            throw new BusinessException('Solo se pueden finalizar viajes En Viaje');
        }

        $horario->update(['estado' => 'Finalizado']);

        /** @var HorarioRuta */
        return $horario->fresh(['ruta', 'bus']);
    }

    public function cancelar(HorarioRuta $horario): HorarioRuta
    {
        if ($horario->estado === 'Finalizado') {
            throw new BusinessException('No se puede cancelar un viaje finalizado');
        }

        $horario->update(['estado' => 'Cancelado']);

        /** @var HorarioRuta */
        return $horario->fresh(['ruta', 'bus']);
    }

    /**
     * @return array{horario_id: int, capacidad_total: int, tiquetes_vendidos: int, asientos_disponibles: int, porcentaje_ocupacion: float}
     */
    public function asientosDisponibles(HorarioRuta $horario): array
    {
        $tiquetesVendidos = $horario->tiquetesDetalle()
            ->where('estado', '!=', 'Cancelado')
            ->count();

        $capacidad = $horario->bus->capacidad_asientos;

        return [
            'horario_id' => $horario->id,
            'capacidad_total' => $capacidad,
            'tiquetes_vendidos' => $tiquetesVendidos,
            'asientos_disponibles' => $capacidad - $tiquetesVendidos,
            'porcentaje_ocupacion' => $capacidad > 0 ? round(($tiquetesVendidos / $capacidad) * 100, 2) : 0,
        ];
    }

    /**
     * Próximos horarios programados con asientos disponibles.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, HorarioRuta>
     */
    public function proximosDisponibles(?int $rutaId = null, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        $query = HorarioRuta::with(['ruta', 'bus'])
            ->where('eliminado', 0)
            ->where('estado', 'Programado')
            ->where('fecha_salida', '>=', now()->toDateString())
            ->where('asientos_disponibles', '>', 0);

        if ($rutaId) {
            $query->where('ruta_id', $rutaId);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, HorarioRuta> */
        return $query->orderBy('fecha_salida')
            ->orderBy('hora_salida')
            ->limit($limit)
            ->get();
    }
}
