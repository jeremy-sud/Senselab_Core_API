<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Contabilidad;

use App\CQRS\Contracts\Query;

/**
 * Query para listar asientos contables con filtros y paginación.
 */
final readonly class ListAsientosQuery implements Query
{
    /**
     * @param string|null $estado Filtrar por estado (Borrador, Aprobado, Mayorizado)
     * @param string|null $fechaDesde Fecha inicio (Y-m-d)
     * @param string|null $fechaHasta Fecha fin (Y-m-d)
     * @param int|null $cuentaContableId Filtrar por cuenta contable
     * @param int $perPage Registros por página
     * @param string $sortBy Campo de ordenamiento
     * @param string $sortDir Dirección (asc, desc)
     */
    public function __construct(
        public ?string $estado = null,
        public ?string $fechaDesde = null,
        public ?string $fechaHasta = null,
        public ?int $cuentaContableId = null,
        public int $perPage = 15,
        public string $sortBy = 'fecha',
        public string $sortDir = 'desc',
    ) {}

    /**
     * @param array<string, mixed> $params
     * @return self
     */
    public static function fromRequest(array $params): self
    {
        return new self(
            estado: $params['estado'] ?? null,
            fechaDesde: $params['desde'] ?? null,
            fechaHasta: $params['hasta'] ?? null,
            cuentaContableId: isset($params['cuenta_contable_id']) ? (int) $params['cuenta_contable_id'] : null,
            perPage: (int) ($params['per_page'] ?? 15),
            sortBy: $params['sort_by'] ?? 'fecha',
            sortDir: $params['sort_dir'] ?? 'desc',
        );
    }

    public function queryName(): string
    {
        return 'contabilidad.list_asientos';
    }
}
