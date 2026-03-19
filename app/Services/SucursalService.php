<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<Sucursal> */
class SucursalService extends BaseService
{
    protected string $modelClass = Sucursal::class;

    protected array $searchFields = ['nombre', 'codigo', 'direccion'];

    protected array $defaultRelations = ['empresa'];

    /**
     * @param Builder<Sucursal> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }
    }

    /** @return array<int, string|array<string, \Closure>> */
    protected function getRelationsForDetail(): array
    {
        return ['empresa', 'almacenes', 'cajas'];
    }

    /**
     * @param array<string, mixed> $data
     * @return Sucursal
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['es_principal'])) {
                Sucursal::where('empresa_id', $data['empresa_id'])
                    ->update(['es_principal' => false]);
            }

            /** @var Sucursal $sucursal */
            $sucursal = Sucursal::create($data);

            return $sucursal->load($this->getRelationsForDetail());
        });
    }

    /**
     * @param Sucursal $model
     * @param array<string, mixed> $data
     * @return Sucursal
     */
    public function actualizar(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            /** @var Sucursal $model */
            if (!empty($data['es_principal'])) {
                Sucursal::where('empresa_id', $model->empresa_id)
                    ->where('id', '!=', $model->id)
                    ->update(['es_principal' => false]);
            }

            $model->update($data);

            return $model->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var Sucursal $model */
        if ($model->es_principal) {
            throw new BusinessException('No se puede eliminar la sucursal principal');
        }

        $model->update(['activo' => false, 'eliminado' => true]);

        return true;
    }
}
