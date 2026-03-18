<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CuentaPorPagar;
use App\Models\PagoCuentaPagar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<PagoCuentaPagar> */
class PagoCuentaPagarService extends BaseService
{
    protected string $modelClass = PagoCuentaPagar::class;

    protected array $defaultRelations = ['cuentaPorPagar', 'formaPago'];

    /**
     * @param Builder<PagoCuentaPagar> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('activo', true)->where('eliminado', false);

        if (!empty($filtros['cuenta_por_pagar_id'])) {
            $query->where('cuenta_por_pagar_id', $filtros['cuenta_por_pagar_id']);
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
     * @return PagoCuentaPagar
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $cuenta = CuentaPorPagar::findOrFail($data['cuenta_por_pagar_id']);
            $saldoPendiente = $cuenta->monto_original - $cuenta->monto_pagado;

            if ($data['monto_pago'] > $saldoPendiente) {
                throw new BusinessException("El monto del pago excede el saldo pendiente ({$saldoPendiente})");
            }

            /** @var PagoCuentaPagar $pago */
            $pago = PagoCuentaPagar::create($data);

            $cuenta->increment('monto_pagado', $data['monto_pago']);

            return $pago->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var PagoCuentaPagar $model */
        return DB::transaction(function () use ($model): bool {
            $cuenta = CuentaPorPagar::findOrFail($model->cuenta_por_pagar_id);
            $cuenta->decrement('monto_pagado', $model->monto_pago);

            $model->update(['activo' => false, 'eliminado' => true]);

            return true;
        });
    }
}
