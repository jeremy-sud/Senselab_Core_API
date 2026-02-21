<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Venta;

use App\CQRS\Contracts\Command;

/**
 * Command para cancelar una venta existente.
 *
 * @package App\CQRS\Commands\Venta
 * @author Sistemas Ursol S.A.
 */
final readonly class CancelVentaCommand implements Command
{
    /**
     * @param int $ventaId ID de la venta a cancelar
     * @param int $usuarioId ID del usuario que cancela
     * @param string $motivo Motivo de la cancelación
     */
    public function __construct(
        public int $ventaId,
        public int $usuarioId,
        public string $motivo,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function commandName(): string
    {
        return 'venta.cancel';
    }
}
