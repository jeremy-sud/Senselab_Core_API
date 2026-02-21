<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;

/**
 * Query para obtener una venta específica por ID.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final readonly class GetVentaQuery implements Query
{
    /**
     * @param int $ventaId ID de la venta
     * @param array<int, string> $relations Relaciones a cargar (eager loading)
     */
    public function __construct(
        public int $ventaId,
        public array $relations = ['cliente', 'detalles.producto', 'empresa', 'sucursal', 'usuario'],
    ) {}

    /**
     * {@inheritDoc}
     */
    public function queryName(): string
    {
        return 'venta.get';
    }
}
