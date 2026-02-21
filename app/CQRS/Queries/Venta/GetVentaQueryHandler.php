<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\VentaResource;
use App\Models\Venta;

/**
 * Handler para GetVentaQuery.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final class GetVentaQueryHandler implements QueryHandler
{
    /**
     * {@inheritDoc}
     *
     * @param GetVentaQuery $query
     */
    public function handle(Query $query): QueryResult
    {
        /** @var GetVentaQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof GetVentaQuery) {
            return QueryResult::failure(
                'Invalid query type. Expected GetVentaQuery.'
            );
        }

        $venta = Venta::with($query->relations)->find($query->ventaId);

        if (!$venta) {
            return QueryResult::notFound(
                "Venta con ID {$query->ventaId} no encontrada"
            );
        }

        return QueryResult::found(
            new VentaResource($venta)
        );
    }
}
