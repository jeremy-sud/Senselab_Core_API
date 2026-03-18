<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\TipoImpuesto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<TipoImpuesto> */
class TipoImpuestoService extends BaseService
{
    protected string $modelClass = TipoImpuesto::class;

    protected array $searchFields = ['nombre', 'codigo_hacienda'];

    protected string $defaultOrderBy = 'nombre';

    /**
     * @param Builder<TipoImpuesto> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }

        $query->where('eliminado', false);
    }

    /**
     * @param Builder<TipoImpuesto> $query
     * @param array<string, mixed> $filtros
     */
    protected function applySearch(Builder $query, array $filtros): void
    {
        $term = $filtros['buscar'] ?? ($filtros['search'] ?? '');
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            $q->where('nombre', 'like', "%{$term}%")
              ->orWhere('codigo_hacienda', 'like', "%{$term}%");
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TipoImpuesto>
     */
    public function activos(): \Illuminate\Database\Eloquent\Collection
    {
        return TipoImpuesto::where('eliminado', false)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    public function obtener(int $id): Model
    {
        return TipoImpuesto::where('eliminado', false)->findOrFail($id);
    }

    protected function beforeDelete(Model $model): void
    {
        /** @var TipoImpuesto $model */
        if ($model->codigo_hacienda === '01') {
            throw new BusinessException(
                'No se puede eliminar el tipo de impuesto IVA (01). Es requerido por Hacienda.'
            );
        }
    }
}
