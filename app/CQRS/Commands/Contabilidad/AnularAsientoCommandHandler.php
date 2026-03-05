<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Contabilidad;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\Services\AsientoContableService;

/**
 * Handler para AnularAsientoCommand.
 */
final class AnularAsientoCommandHandler implements CommandHandler
{
    public function __construct(
        private AsientoContableService $service
    ) {}

    public function handle(Command $command): CommandResult
    {
        /** @var AnularAsientoCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof AnularAsientoCommand) {
            return CommandResult::failure('Invalid command type. Expected AnularAsientoCommand.');
        }

        try {
            $asiento = $this->service->obtener($command->asientoId);

            if (!$asiento) {
                return CommandResult::failure("Asiento contable #{$command->asientoId} no encontrado.");
            }

            if ($asiento->estado === 'Mayorizado') {
                return CommandResult::failure('No se puede anular un asiento mayorizado.');
            }

            $this->service->eliminar($asiento);

            return CommandResult::success(
                id: $asiento->id,
                message: 'Asiento contable anulado exitosamente',
                metadata: ['motivo' => $command->motivo]
            );
        } catch (\Throwable $e) {
            return CommandResult::failure(
                message: 'Error al anular asiento: ' . $e->getMessage()
            );
        }
    }
}
