<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Compra;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\OrdenCompraResource;
use App\Services\OrdenCompraService;

/**
 * Handler para ListOrdenesCompraQuery.
 *
 * Delega al OrdenCompraService con los filtros proporcionados.
 */
final class ListOrdenesCompraQueryHandler implements QueryHandler
{
    public function __construct(
        private OrdenCompraService $service
    ) {}

    public function handle(Query $query): QueryResult
    {
        /** @var ListOrdenesCompraQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof ListOrdenesCompraQuery) {
            return QueryResult::notFound('Invalid query type.');
        }

        $filtros = array_filter([
            'empresa_id' => $query->empresaId,
            'proveedor_id' => $query->proveedorId,
            'estado' => $query->estado,
            'pendientes' => $query->pendientes,
            'activas' => $query->activas,
        ], fn ($v) => $v !== null);

        $paginator = $this->service->listar($filtros, $query->perPage);

        $paginator->getCollection()->transform(
            fn ($orden) => new OrdenCompraResource($orden)
        );

        return QueryResult::paginated($paginator);
    }
}
