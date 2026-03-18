<?php

namespace App\Services;

use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Builder;

/** @extends BaseService<FormaPago> */
class FormaPagoService extends BaseService
{
    protected string $modelClass = FormaPago::class;

    protected array $searchFields = ['nombre', 'codigo_dgt'];

    /**
     * @param Builder<FormaPago> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        parent::applyFilters($query, $filtros);

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }
    }
}
