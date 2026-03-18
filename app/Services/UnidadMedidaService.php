<?php

namespace App\Services;

use App\Models\UnidadMedida;

/** @extends BaseService<UnidadMedida> */
class UnidadMedidaService extends BaseService
{
    protected string $modelClass = UnidadMedida::class;

    protected array $searchFields = ['nombre', 'codigo_dgt'];
}
