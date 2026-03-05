<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Compra;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\OrdenCompraResource;
use App\Services\OrdenCompraService;

/**
 * Handler para GetOrdenCompraQuery.
 */
final class GetOrdenCompraQueryHandler implements QueryHandler
{
    public function __construct(
        private OrdenCompraService $service
    ) {}

    public function handle(Query $query): QueryResult
    {
        /** @var GetOrdenCompraQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof GetOrdenCompraQuery) {
            return QueryResult::notFound('Invalid query type.');
        }

        try {
            $orden = $this->service->obtener($query->ordenId);
            $orden->loadMissing($query->relations);

            return QueryResult::found(new OrdenCompraResource($orden));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return QueryResult::notFound("Orden de compra #{$query->ordenId} no encontrada.");
        }
    }
}
