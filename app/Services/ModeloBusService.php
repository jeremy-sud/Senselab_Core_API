<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\ModeloBus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** @extends BaseService<ModeloBus> */
class ModeloBusService extends BaseService
{
    protected string $modelClass = ModeloBus::class;

    protected array $searchFields = ['nombre'];

    protected string $defaultOrderBy = 'nombre';

    public function eliminar(Model $model): bool
    {
        /** @var ModeloBus $model */
        if ($model->busesUnidades()->exists()) {
            throw new BusinessException('No se puede eliminar un modelo con buses asociados');
        }

        $model->delete();

        return true;
    }

    /**
     * Listado simplificado para selectores.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ModeloBus>
     */
    public function activos(): \Illuminate\Database\Eloquent\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, ModeloBus> */
        return ModeloBus::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();
    }
}
