<?php

namespace App\Services;

use App\Models\Marca;

/** @extends BaseService<Marca> */
class MarcaService extends BaseService
{
    protected string $modelClass = Marca::class;

    protected array $searchFields = ['nombre'];
}
