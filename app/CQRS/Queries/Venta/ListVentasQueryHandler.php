<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;

/**
 * Handler para ListVentasQuery.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final class ListVentasQueryHandler implements QueryHandler
{
    /**
     * {@inheritDoc}
     *
     * @param ListVentasQuery $query
     */
    public function handle(Query $query): QueryResult
    {
        /** @var ListVentasQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof ListVentasQuery) {
            return QueryResult::failure(
                'Invalid query type. Expected ListVentasQuery.'
            );
        }

        $builder = Venta::query()
            ->with(['cliente', 'sucursal', 'empresa']);

        $this->applyFilters($builder, $query);
        $this->applySorting($builder, $query);

        $paginator = $builder->paginate(
            perPage: $query->perPage,
            page: $query->page
        );

        // Transformar con el Resource
        $paginator->getCollection()->transform(function ($venta) {
            return new VentaResource($venta);
        });

        return QueryResult::paginated($paginator);
    }

    /**
     * Aplica los filtros a la consulta.
     *
     * @param Builder<Venta> $builder
     * @param ListVentasQuery $query
     */
    private function applyFilters(Builder $builder, ListVentasQuery $query): void
    {
        if ($query->clienteId !== null) {
            $builder->where('cliente_id', $query->clienteId);
        }

        if ($query->sucursalId !== null) {
            $builder->where('sucursal_id', $query->sucursalId);
        }

        if ($query->estado !== null) {
            $builder->where('estado_venta', $query->estado);
        }

        if ($query->fechaDesde !== null) {
            $builder->whereDate('fecha_venta', '>=', $query->fechaDesde);
        }

        if ($query->fechaHasta !== null) {
            $builder->whereDate('fecha_venta', '<=', $query->fechaHasta);
        }

        if ($query->search !== null) {
            $builder->where('numero_comprobante', 'like', "%{$query->search}%");
        }
    }

    /**
     * Aplica el ordenamiento a la consulta.
     *
     * @param Builder<Venta> $builder
     * @param ListVentasQuery $query
     */
    private function applySorting(Builder $builder, ListVentasQuery $query): void
    {
        $allowedFields = ['id', 'fecha_venta', 'monto_total_venta', 'estado_venta', 'created_at'];

        $sortBy = in_array($query->sortBy, $allowedFields, true) ? $query->sortBy : 'id';
        $sortDir = strtolower($query->sortDir) === 'asc' ? 'asc' : 'desc';

        $builder->orderBy($sortBy, $sortDir);
    }
}
