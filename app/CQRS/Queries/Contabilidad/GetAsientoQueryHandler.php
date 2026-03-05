<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Contabilidad;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\AsientoContableResource;
use App\Services\AsientoContableService;

/**
 * Handler para GetAsientoQuery.
 */
final class GetAsientoQueryHandler implements QueryHandler
{
    public function __construct(
        private AsientoContableService $service
    ) {}

    public function handle(Query $query): QueryResult
    {
        /** @var GetAsientoQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof GetAsientoQuery) {
            return QueryResult::notFound('Invalid query type.');
        }

        $asiento = $this->service->obtener($query->asientoId);

        if (!$asiento) {
            return QueryResult::notFound("Asiento contable #{$query->asientoId} no encontrado.");
        }

        $asiento->loadMissing($query->relations);

        return QueryResult::found(new AsientoContableResource($asiento));
    }
}
