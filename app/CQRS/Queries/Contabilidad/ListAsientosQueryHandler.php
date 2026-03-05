<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Contabilidad;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\AsientoContableResource;
use App\Services\AsientoContableService;

/**
 * Handler para ListAsientosQuery.
 *
 * Delega al AsientoContableService según los filtros proporcionados.
 */
final class ListAsientosQueryHandler implements QueryHandler
{
    public function __construct(
        private AsientoContableService $service
    ) {}

    public function handle(Query $query): QueryResult
    {
        /** @var ListAsientosQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof ListAsientosQuery) {
            return QueryResult::notFound('Invalid query type.');
        }

        $paginator = match (true) {
            $query->estado !== null
                => $this->service->porEstado($query->estado, $query->perPage),

            $query->fechaDesde !== null && $query->fechaHasta !== null
                => $this->service->entreFechas(
                    new \DateTime($query->fechaDesde),
                    new \DateTime($query->fechaHasta),
                    $query->perPage
                ),

            $query->cuentaContableId !== null
                => $this->service->porCuenta($query->cuentaContableId, $query->perPage),

            default => $this->service->listar($query->perPage)
        };

        $paginator->getCollection()->transform(
            fn ($asiento) => new AsientoContableResource($asiento)
        );

        return QueryResult::paginated($paginator);
    }
}
