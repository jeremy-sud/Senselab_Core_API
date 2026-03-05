<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Compra;

use App\CQRS\Contracts\Query;

/**
 * Query para obtener una orden de compra con detalles.
 */
final readonly class GetOrdenCompraQuery implements Query
{
    /**
     * @param int $ordenId
     * @param array<int, string> $relations
     */
    public function __construct(
        public int $ordenId,
        public array $relations = ['proveedor', 'detalles.producto', 'empresa'],
    ) {}

    public function queryName(): string
    {
        return 'compras.get_orden';
    }
}
