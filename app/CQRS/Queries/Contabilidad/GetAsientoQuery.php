<?php

declare(strict_types=1);

namespace App\CQRS\Queries\Contabilidad;

use App\CQRS\Contracts\Query;

/**
 * Query para obtener un asiento contable con sus detalles.
 */
final readonly class GetAsientoQuery implements Query
{
    /**
     * @param int $asientoId
     * @param array<int, string> $relations
     */
    public function __construct(
        public int $asientoId,
        public array $relations = ['detalles.cuentaContable', 'empresa'],
    ) {}

    public function queryName(): string
    {
        return 'contabilidad.get_asiento';
    }
}
