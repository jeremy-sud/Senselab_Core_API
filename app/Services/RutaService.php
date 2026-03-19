<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Ruta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<Ruta> */
class RutaService extends BaseService
{
    protected string $modelClass = Ruta::class;

    protected array $searchFields = ['nombre', 'origen', 'destino'];

    protected array $defaultRelations = ['empresa'];

    /**
     * @param Builder<Ruta> $query
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

        if (!empty($filtros['origen'])) {
            $query->where('origen', 'like', '%' . $filtros['origen'] . '%');
        }

        if (!empty($filtros['destino'])) {
            $query->where('destino', 'like', '%' . $filtros['destino'] . '%');
        }
    }

    public function obtener(int $id): Model
    {
        /** @var Ruta */
        return Ruta::where('eliminado', 0)
            ->with(['empresa', 'horariosRuta'])
            ->findOrFail($id);
    }

    public function obtenerPorEmpresa(int $id, int $empresaId): Ruta
    {
        /** @var Ruta */
        return Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->with(['empresa', 'horariosRuta'])
            ->findOrFail($id);
    }

    public function eliminar(Model $model): bool
    {
        /** @var Ruta $model */
        if ($model->horariosRuta()->where('estado', '!=', 'Finalizado')->exists()) {
            throw new BusinessException('No se puede eliminar una ruta con horarios activos');
        }

        $model->update(['eliminado' => 1, 'activo' => 0]);

        return true;
    }

    /**
     * Rutas activas para selectores.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Ruta>
     */
    public function activas(int $empresaId): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Ruta> */
        return Ruta::where('empresa_id', $empresaId)
            ->where('eliminado', 0)
            ->where('activo', 1)
            ->select('id', 'nombre', 'origen', 'destino', 'tarifa_base', 'duracion_estimada')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Calcula tarifa considerando pasajeros y descuento.
     *
     * @return array{tarifa_base_unitaria: string, cantidad_pasajeros: int, subtotal: string, descuento_porcentaje: float, monto_descuento: string, tarifa_final: string}
     */
    public function calcularTarifa(Ruta $ruta, int $cantidadPasajeros = 1, float $descuento = 0): array
    {
        $tarifaBase = $ruta->tarifa_base * $cantidadPasajeros;
        $montoDescuento = ($tarifaBase * $descuento) / 100;
        $tarifaFinal = $tarifaBase - $montoDescuento;

        return [
            'tarifa_base_unitaria' => number_format($ruta->tarifa_base, 2),
            'cantidad_pasajeros' => $cantidadPasajeros,
            'subtotal' => number_format($tarifaBase, 2),
            'descuento_porcentaje' => $descuento,
            'monto_descuento' => number_format($montoDescuento, 2),
            'tarifa_final' => number_format($tarifaFinal, 2),
        ];
    }

    /**
     * Estadísticas de viajes de una ruta.
     *
     * @return array{total_viajes: int, finalizados: int, en_curso: int, programados: int}
     */
    public function estadisticas(Ruta $ruta): array
    {
        $horarios = $ruta->horariosRuta();

        return [
            'total_viajes' => $horarios->count(),
            'finalizados' => (clone $horarios)->where('estado', 'Finalizado')->count(),
            'en_curso' => (clone $horarios)->where('estado', 'En Viaje')->count(),
            'programados' => (clone $horarios)->where('estado', 'Programado')->count(),
        ];
    }
}
