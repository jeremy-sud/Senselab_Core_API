<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;

/**
 * Query para obtener estadísticas de ventas.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final readonly class VentasStatsQuery implements Query
{
    /**
     * @param string|null $fechaDesde Fecha inicio (Y-m-d)
     * @param string|null $fechaHasta Fecha fin (Y-m-d)
     * @param int|null $sucursalId Filtrar por sucursal
     */
    public function __construct(
        public ?string $fechaDesde = null,
        public ?string $fechaHasta = null,
        public ?int $sucursalId = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function queryName(): string
    {
        return 'venta.stats';
    }
}
