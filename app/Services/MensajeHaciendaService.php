<?php

namespace App\Services;

use App\Models\MensajeHacienda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<MensajeHacienda> */
class MensajeHaciendaService extends BaseService
{
    protected string $modelClass = MensajeHacienda::class;

    protected array $defaultRelations = ['comprobante'];

    protected string $defaultOrderBy = 'fecha_emision';
    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<MensajeHacienda> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['tipo_mensaje'])) {
            $query->porTipo($filtros['tipo_mensaje']);
        }

        if (!empty($filtros['comprobante_id'])) {
            $query->where('comprobante_id', $filtros['comprobante_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_emision', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_emision', '<=', $filtros['fecha_hasta']);
        }
    }

    /**
     * Asigna fecha_procesamiento si estado es 'procesado'.
     *
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void
    {
        if (($data['estado'] ?? 'pendiente') === 'procesado') {
            $data['fecha_procesamiento'] = now();
        }

        $data['intentos_envio'] = $data['intentos_envio'] ?? 0;
    }

    /**
     * Asigna fecha_procesamiento si se cambia estado a 'procesado'.
     *
     * @param MensajeHacienda $model
     * @param array<string, mixed> $data
     */
    protected function beforeUpdate(Model $model, array &$data): void
    {
        if (!empty($data['estado']) && $data['estado'] === 'procesado') {
            $data['fecha_procesamiento'] = now();
        }
    }

    /**
     * Soft delete con flag (no usa el patrón activo/eliminado de BaseService).
     */
    public function eliminar(Model $model): bool
    {
        /** @var MensajeHacienda $model */
        $model->eliminado = now();
        $model->save();

        return true;
    }

    /**
     * @return array<int, string|array<string, \Closure>>
     */
    protected function getRelationsForDetail(): array
    {
        return ['comprobante', 'empresa'];
    }
}
