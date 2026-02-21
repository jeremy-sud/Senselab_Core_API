<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;
use App\CQRS\Contracts\QueryHandler;
use App\CQRS\Contracts\QueryResult;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Handler para VentasStatsQuery.
 *
 * Calcula estadísticas agregadas de ventas.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final class VentasStatsQueryHandler implements QueryHandler
{
    /**
     * {@inheritDoc}
     *
     * @param VentasStatsQuery $query
     */
    public function handle(Query $query): QueryResult
    {
        /** @var VentasStatsQuery $query */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$query instanceof VentasStatsQuery) {
            return QueryResult::failure(
                'Invalid query type. Expected VentasStatsQuery.'
            );
        }

        $builder = Venta::query();
        $this->applyFilters($builder, $query);

        // Obtener estadísticas agregadas
        $stats = $builder->selectRaw("
            COUNT(*) as total_ventas,
            COALESCE(SUM(monto_total_venta), 0) as monto_total,
            COALESCE(AVG(monto_total_venta), 0) as ticket_promedio,
            COUNT(DISTINCT cliente_id) as clientes_unicos,
            SUM(CASE WHEN estado_venta = 'pagada' THEN 1 ELSE 0 END) as ventas_pagadas,
            SUM(CASE WHEN estado_venta = 'pendiente' THEN 1 ELSE 0 END) as ventas_pendientes,
            SUM(CASE WHEN estado_venta = 'anulada' THEN 1 ELSE 0 END) as ventas_anuladas
        ")->first();

        // Obtener ventas por estado
        $porEstado = $builder->clone()
            ->select('estado_venta', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(monto_total_venta) as total'))
            ->groupBy('estado_venta')
            ->get()
            ->keyBy('estado_venta');

        // Top 5 clientes
        $topClientes = $builder->clone()
            ->select('cliente_id', DB::raw('COUNT(*) as ventas'), DB::raw('SUM(monto_total_venta) as total'))
            ->with('cliente:id,nombre,identificacion')
            ->groupBy('cliente_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return QueryResult::found([
            'resumen' => [
                'total_ventas' => (int) ($stats->total_ventas ?? 0),
                'monto_total' => round((float) ($stats->monto_total ?? 0), 2),
                'ticket_promedio' => round((float) ($stats->ticket_promedio ?? 0), 2),
                'clientes_unicos' => (int) ($stats->clientes_unicos ?? 0),
            ],
            'por_estado' => [
                'pagadas' => (int) ($stats->ventas_pagadas ?? 0),
                'pendientes' => (int) ($stats->ventas_pendientes ?? 0),
                'anuladas' => (int) ($stats->ventas_anuladas ?? 0),
            ],
            'desglose_estado' => $porEstado->toArray(),
            /** @phpstan-ignore argument.unresolvableType, method.unresolvableReturnType */
            'top_clientes' => $topClientes->map(function ($item): array {
                /** @var \App\Models\Venta $item */
                return [
                    'cliente_id' => $item->cliente_id,
                    'nombre' => $item->cliente->nombre ?? 'N/A',
                    'ventas' => $item->ventas,
                    'total' => round((float) $item->total, 2),
                ];
            })->toArray(),
            'periodo' => [
                'fecha_desde' => $query->fechaDesde,
                'fecha_hasta' => $query->fechaHasta,
            ],
        ]);
    }

    /**
     * Aplica los filtros a la consulta.
     *
     * @param Builder<Venta> $builder
     * @param VentasStatsQuery $query
     */
    private function applyFilters(Builder $builder, VentasStatsQuery $query): void
    {
        if ($query->sucursalId !== null) {
            $builder->where('sucursal_id', $query->sucursalId);
        }

        if ($query->fechaDesde !== null) {
            $builder->whereDate('fecha_venta', '>=', $query->fechaDesde);
        }

        if ($query->fechaHasta !== null) {
            $builder->whereDate('fecha_venta', '<=', $query->fechaHasta);
        }
    }
}
