<?php

namespace App\Services;

use App\Events\PagoRecibidoEvent;
use App\Exceptions\BusinessException;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<Pago> */
class PagoService extends BaseService
{
    protected string $modelClass = Pago::class;

    protected array $defaultRelations = ['formaPago', 'proveedor', 'cliente', 'cuentaPorPagar', 'cuentaPorCobrar', 'ordenCompra'];

    protected string $defaultOrderBy = 'fecha_pago';

    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<Pago> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('eliminado', false);

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['forma_pago_id'])) {
            $query->where('forma_pago_id', $filtros['forma_pago_id']);
        }

        if (!empty($filtros['proveedor_id'])) {
            $query->where('proveedor_id', $filtros['proveedor_id']);
        }

        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['desde']) && !empty($filtros['hasta'])) {
            $query->whereBetween('fecha_pago', [$filtros['desde'], $filtros['hasta']]);
        }
    }

    public function obtener(int $id): Model
    {
        /** @var Pago */
        return Pago::where('eliminado', false)
            ->with($this->getRelationsForDetail())
            ->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return Pago
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            /** @var Pago $pago */
            $pago = Pago::create($data);

            if ($pago->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($pago->cuenta_por_pagar_id, (float) $pago->monto);
            }
            if ($pago->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($pago->cuenta_por_cobrar_id, (float) $pago->monto);
            }

            $pago->load($this->getRelationsForDetail());

            PagoRecibidoEvent::dispatch($pago->empresa_id, [
                'pago_id' => $pago->id,
                'monto' => $pago->monto,
                'estado' => $pago->estado,
                'forma_pago_id' => $pago->forma_pago_id,
                'cliente_id' => $pago->cliente_id,
                'proveedor_id' => $pago->proveedor_id,
            ]);

            return $pago;
        });
    }

    /**
     * @param Pago $model
     * @param array<string, mixed> $data
     * @return Pago
     */
    public function actualizar(Model $model, array $data): Model
    {
        /** @var Pago $model */
        if ($model->estado === 'pagado') {
            throw new BusinessException('No se puede modificar un pago ya procesado');
        }

        return DB::transaction(function () use ($model, $data) {
            $montoAnterior = (float) $model->monto;
            $model->update($data);

            if (isset($data['monto']) && (float) $data['monto'] !== $montoAnterior) {
                $diferencia = (float) $data['monto'] - $montoAnterior;
                if ($model->cuenta_por_pagar_id) {
                    $this->actualizarCuentaPorPagar($model->cuenta_por_pagar_id, $diferencia);
                }
                if ($model->cuenta_por_cobrar_id) {
                    $this->actualizarCuentaPorCobrar($model->cuenta_por_cobrar_id, $diferencia);
                }
            }

            return $model->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var Pago $model */
        if ($model->estado === 'pagado') {
            throw new BusinessException('No se puede eliminar un pago ya procesado');
        }

        return DB::transaction(function () use ($model): bool {
            if ($model->cuenta_por_pagar_id) {
                $this->actualizarCuentaPorPagar($model->cuenta_por_pagar_id, -(float) $model->monto);
            }
            if ($model->cuenta_por_cobrar_id) {
                $this->actualizarCuentaPorCobrar($model->cuenta_por_cobrar_id, -(float) $model->monto);
            }

            $model->update(['activo' => false, 'eliminado' => true]);

            return true;
        });
    }

    /**
     * Resumen de pagos agrupados por forma de pago.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Pago>
     */
    public function resumenPorFormaPago(int $empresaId): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Pago> */
        return Pago::where('empresa_id', $empresaId)
            ->where('eliminado', false)
            ->where('estado', 'pagado')
            ->select('forma_pago_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto) as total'))
            ->groupBy('forma_pago_id')
            ->with('formaPago')
            ->get();
    }

    private function actualizarCuentaPorPagar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorPagar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        $nuevoEstado = match (true) {
            $cuenta->monto_pagado >= $cuenta->monto_original => 'Pagada Totalmente',
            $cuenta->monto_pagado > 0 => 'Pagada Parcialmente',
            default => $cuenta->estado
        };

        if ($nuevoEstado !== $cuenta->estado) {
            $cuenta->update(['estado' => $nuevoEstado]);
        }
    }

    private function actualizarCuentaPorCobrar(int $cuentaId, float $monto): void
    {
        $cuenta = CuentaPorCobrar::findOrFail($cuentaId);
        $cuenta->increment('monto_pagado', $monto);

        $nuevoEstado = match (true) {
            $cuenta->monto_pagado >= $cuenta->monto_original => 'Pagada Totalmente',
            $cuenta->monto_pagado > 0 => 'Pagada Parcialmente',
            default => $cuenta->estado
        };

        if ($nuevoEstado !== $cuenta->estado) {
            $cuenta->update(['estado' => $nuevoEstado]);
        }
    }
}
