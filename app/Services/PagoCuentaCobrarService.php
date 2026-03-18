<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CuentaPorCobrar;
use App\Models\PagoCuentaCobrar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<PagoCuentaCobrar> */
class PagoCuentaCobrarService extends BaseService
{
    protected string $modelClass = PagoCuentaCobrar::class;

    protected array $defaultRelations = ['cuentaPorCobrar', 'formaPago'];

    /**
     * @param Builder<PagoCuentaCobrar> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('activo', true)->where('eliminado', false);

        if (!empty($filtros['cuenta_por_cobrar_id'])) {
            $query->where('cuenta_por_cobrar_id', $filtros['cuenta_por_cobrar_id']);
        }

        if (!empty($filtros['forma_pago_id'])) {
            $query->where('forma_pago_id', $filtros['forma_pago_id']);
        }

        if (!empty($filtros['fecha_desde']) && !empty($filtros['fecha_hasta'])) {
            $query->whereBetween('fecha_pago', [$filtros['fecha_desde'], $filtros['fecha_hasta']]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return PagoCuentaCobrar
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $cuenta = CuentaPorCobrar::findOrFail($data['cuenta_por_cobrar_id']);
            $saldoPendiente = $cuenta->monto_original - $cuenta->monto_pagado;

            if ($data['monto_pago'] > $saldoPendiente) {
                throw new BusinessException("El monto del pago excede el saldo pendiente ({$saldoPendiente})");
            }

            /** @var PagoCuentaCobrar $pago */
            $pago = PagoCuentaCobrar::create($data);

            $cuenta->increment('monto_pagado', $data['monto_pago']);

            return $pago->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var PagoCuentaCobrar $model */
        return DB::transaction(function () use ($model): bool {
            $cuenta = CuentaPorCobrar::findOrFail($model->cuenta_por_cobrar_id);
            $cuenta->decrement('monto_pagado', $model->monto_pago);

            $model->update(['activo' => false, 'eliminado' => true]);

            return true;
        });
    }
}
