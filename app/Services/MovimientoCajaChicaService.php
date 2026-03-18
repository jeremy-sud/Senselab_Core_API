<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CajaChica;
use App\Models\MovimientoCajaChica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<MovimientoCajaChica> */
class MovimientoCajaChicaService extends BaseService
{
    protected string $modelClass = MovimientoCajaChica::class;

    protected array $defaultRelations = ['cajaChica', 'cuentaContable'];

    /**
     * @param Builder<MovimientoCajaChica> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('activo', true)->where('eliminado', false);

        if (!empty($filtros['caja_chica_id'])) {
            $query->where('caja_chica_id', $filtros['caja_chica_id']);
        }

        if (!empty($filtros['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filtros['tipo_movimiento']);
        }

        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->whereBetween('fecha_movimiento', [$filtros['fecha_desde'], $filtros['fecha_hasta']]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return MovimientoCajaChica
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $cajaChica = CajaChica::findOrFail($data['caja_chica_id']);

            if (!$cajaChica->estaAbierta()) {
                throw new BusinessException('Solo se pueden registrar movimientos en fondos abiertos');
            }

            if ($data['tipo_movimiento'] === MovimientoCajaChica::TIPO_EGRESO) {
                if ($cajaChica->saldo_actual < $data['monto']) {
                    throw new BusinessException("Saldo insuficiente en caja chica. Saldo actual: {$cajaChica->saldo_actual}");
                }
            }

            /** @var MovimientoCajaChica $movimiento */
            $movimiento = MovimientoCajaChica::create($data);

            if ($data['tipo_movimiento'] === MovimientoCajaChica::TIPO_EGRESO) {
                $cajaChica->decrement('saldo_actual', $data['monto']);
            } else {
                $cajaChica->increment('saldo_actual', $data['monto']);
            }

            return $movimiento->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var MovimientoCajaChica $model */
        return DB::transaction(function () use ($model): bool {
            $cajaChica = CajaChica::findOrFail($model->caja_chica_id);

            if ($model->tipo_movimiento === MovimientoCajaChica::TIPO_EGRESO) {
                $cajaChica->increment('saldo_actual', $model->monto);
            } else {
                $cajaChica->decrement('saldo_actual', $model->monto);
            }

            $model->update(['activo' => false, 'eliminado' => true]);

            return true;
        });
    }
}
