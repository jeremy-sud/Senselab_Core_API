<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Contabilidad;

use App\CQRS\Contracts\Command;

/**
 * Command para anular un asiento contable.
 */
final readonly class AnularAsientoCommand implements Command
{
    public function __construct(
        public int $asientoId,
        public string $motivo,
    ) {}

    public function commandName(): string
    {
        return 'contabilidad.anular_asiento';
    }
}
