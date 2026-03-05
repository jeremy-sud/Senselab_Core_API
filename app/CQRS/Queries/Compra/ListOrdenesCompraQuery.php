<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Compra;

use App\CQRS\Contracts\Query;

/**
 * Query para listar órdenes de compra con filtros y paginación.
 */
final readonly class ListOrdenesCompraQuery implements Query
{
    /**
     * @param int|null $empresaId Filtrar por empresa
     * @param int|null $proveedorId Filtrar por proveedor
     * @param string|null $estado Filtrar por estado
     * @param bool|null $pendientes Solo órdenes pendientes
     * @param bool|null $activas Solo órdenes activas
     * @param int $perPage Registros por página
     */
    public function __construct(
        public ?int $empresaId = null,
        public ?int $proveedorId = null,
        public ?string $estado = null,
        public ?bool $pendientes = null,
        public ?bool $activas = null,
        public int $perPage = 15,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return self
     */
    public static function fromRequest(array $params): self
    {
        return new self(
            empresaId: isset($params['empresa_id']) ? (int) $params['empresa_id'] : null,
            proveedorId: isset($params['proveedor_id']) ? (int) $params['proveedor_id'] : null,
            estado: $params['estado'] ?? null,
            pendientes: isset($params['pendientes']) ? filter_var($params['pendientes'], FILTER_VALIDATE_BOOLEAN) : null,
            activas: isset($params['activas']) ? filter_var($params['activas'], FILTER_VALIDATE_BOOLEAN) : null,
            perPage: (int) ($params['per_page'] ?? 15),
        );
    }

    public function queryName(): string
    {
        return 'compras.list_ordenes';
    }
}
