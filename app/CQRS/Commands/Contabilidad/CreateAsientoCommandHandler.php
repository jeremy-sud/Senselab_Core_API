<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Contabilidad;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\Services\AsientoContableService;

/**
 * Handler para CreateAsientoCommand.
 *
 * Delega la creación al AsientoContableService existente.
 */
final class CreateAsientoCommandHandler implements CommandHandler
{
    public function __construct(
        private AsientoContableService $service
    ) {}

    public function handle(Command $command): CommandResult
    {
        /** @var CreateAsientoCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof CreateAsientoCommand) {
            return CommandResult::failure('Invalid command type. Expected CreateAsientoCommand.');
        }

        try {
            $asiento = $this->service->crear([
                'empresa_id' => $command->empresaId,
                'fecha' => $command->fecha,
                'descripcion' => $command->descripcion,
                'referencia' => $command->referencia,
                'observaciones' => $command->observaciones,
                'detalles' => $command->detalles,
            ]);

            return CommandResult::success(
                id: $asiento->id,
                message: 'Asiento contable creado exitosamente',
                metadata: [
                    'numero_asiento' => $asiento->numero_asiento ?? null,
                    'total_debe' => $asiento->detalles->sum('debe'),
                    'total_haber' => $asiento->detalles->sum('haber'),
                    'estado' => $asiento->estado,
                ]
            );
        } catch (\Throwable $e) {
            return CommandResult::failure(
                message: 'Error al crear asiento contable: ' . $e->getMessage()
            );
        }
    }
}
