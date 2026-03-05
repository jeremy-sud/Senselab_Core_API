<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Compra;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\Services\OrdenCompraService;

/**
 * Handler para CreateOrdenCompraCommand.
 *
 * Delega la creación al OrdenCompraService existente.
 */
final class CreateOrdenCompraCommandHandler implements CommandHandler
{
    public function __construct(
        private OrdenCompraService $service
    ) {}

    public function handle(Command $command): CommandResult
    {
        /** @var CreateOrdenCompraCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof CreateOrdenCompraCommand) {
            return CommandResult::failure('Invalid command type. Expected CreateOrdenCompraCommand.');
        }

        try {
            $orden = $this->service->crear(
                data: [
                    'empresa_id' => $command->empresaId,
                    'proveedor_id' => $command->proveedorId,
                    'fecha_orden' => $command->fechaOrden,
                    'fecha_entrega_esperada' => $command->fechaEntregaEsperada,
                    'moneda' => $command->moneda,
                    'observaciones' => $command->observaciones,
                ],
                detalles: $command->detalles
            );

            return CommandResult::success(
                id: $orden->id,
                message: 'Orden de compra creada exitosamente',
                metadata: [
                    'numero_orden' => $orden->numero_orden ?? null,
                    'estado' => $orden->estado,
                    'total' => $orden->total ?? null,
                ]
            );
        } catch (\Throwable $e) {
            return CommandResult::failure(
                message: 'Error al crear orden de compra: ' . $e->getMessage()
            );
        }
    }
}
