<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Venta;

use App\CQRS\Contracts\Query;

/**
 * Query para listar ventas con filtros y paginación.
 *
 * @package App\CQRS\Queries\Venta
 * @author Sistemas Ursol S.A.
 */
final readonly class ListVentasQuery implements Query
{
    /**
     * @param int|null $clienteId Filtrar por cliente
     * @param int|null $sucursalId Filtrar por sucursal
     * @param string|null $estado Filtrar por estado (pendiente, pagada, parcial, anulada)
     * @param string|null $fechaDesde Fecha inicio (Y-m-d)
     * @param string|null $fechaHasta Fecha fin (Y-m-d)
     * @param string|null $search Búsqueda por número de comprobante
     * @param int $perPage Registros por página
     * @param int $page Página actual
     * @param string $sortBy Campo para ordenar
     * @param string $sortDir Dirección de orden (asc, desc)
     */
    public function __construct(
        public ?int $clienteId = null,
        public ?int $sucursalId = null,
        public ?string $estado = null,
        public ?string $fechaDesde = null,
        public ?string $fechaHasta = null,
        public ?string $search = null,
        public int $perPage = 15,
        public int $page = 1,
        public string $sortBy = 'id',
        public string $sortDir = 'desc',
    ) {}

    /**
     * Crea la query desde parámetros del request.
     *
     * @param array<string, mixed> $params
     * @return self
     */
    public static function fromRequest(array $params): self
    {
        return new self(
            clienteId: isset($params['cliente_id']) ? (int) $params['cliente_id'] : null,
            sucursalId: isset($params['sucursal_id']) ? (int) $params['sucursal_id'] : null,
            estado: $params['estado'] ?? null,
            fechaDesde: $params['fecha_desde'] ?? null,
            fechaHasta: $params['fecha_hasta'] ?? null,
            search: $params['search'] ?? null,
            perPage: isset($params['per_page']) ? (int) $params['per_page'] : 15,
            page: isset($params['page']) ? (int) $params['page'] : 1,
            sortBy: $params['sort_by'] ?? 'id',
            sortDir: $params['sort_dir'] ?? 'desc',
        );
    }

    /**
     * {@inheritDoc}
     */
    public function queryName(): string
    {
        return 'venta.list';
    }

    /**
     * Verifica si hay filtros aplicados.
     *
     * @return bool
     */
    public function hasFilters(): bool
    {
        return $this->clienteId !== null
            || $this->sucursalId !== null
            || $this->estado !== null
            || $this->fechaDesde !== null
            || $this->fechaHasta !== null
            || $this->search !== null;
    }
}
