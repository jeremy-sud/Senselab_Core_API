<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Compra;

use App\CQRS\Contracts\Command;

/**
 * Command para cancelar una orden de compra.
 */
final readonly class CancelOrdenCompraCommand implements Command
{
    public function __construct(
        public int $ordenId,
        public string $motivo,
    ) {}

    public function commandName(): string
    {
        return 'compras.cancelar_orden';
    }
}
