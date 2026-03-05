<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Compra;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\Services\OrdenCompraService;

/**
 * Handler para CancelOrdenCompraCommand.
 */
final class CancelOrdenCompraCommandHandler implements CommandHandler
{
    public function __construct(
        private OrdenCompraService $service
    ) {}

    public function handle(Command $command): CommandResult
    {
        /** @var CancelOrdenCompraCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof CancelOrdenCompraCommand) {
            return CommandResult::failure('Invalid command type. Expected CancelOrdenCompraCommand.');
        }

        try {
            $orden = $this->service->obtener($command->ordenId);

            if (in_array($orden->estado, ['recibida_completa', 'cancelada'], true)) {
                return CommandResult::failure(
                    "No se puede cancelar una orden en estado '{$orden->estado}'."
                );
            }

            $this->service->eliminar($orden);

            return CommandResult::success(
                id: $orden->id,
                message: 'Orden de compra cancelada exitosamente',
                metadata: ['motivo' => $command->motivo]
            );
        } catch (\Throwable $e) {
            return CommandResult::failure(
                message: 'Error al cancelar orden: ' . $e->getMessage()
            );
        }
    }
}
