<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Venta;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Support\Facades\Log;

/**
 * Handler para el comando CancelVentaCommand.
 *
 * @package App\CQRS\Commands\Venta
 * @author Sistemas Ursol S.A.
 */
final class CancelVentaCommandHandler implements CommandHandler
{
    /**
     * @param VentaService $ventaService Servicio de ventas
     */
    public function __construct(
        private VentaService $ventaService
    ) {}

    /**
     * {@inheritDoc}
     *
     * @param CancelVentaCommand $command
     */
    public function handle(Command $command): CommandResult
    {
        /** @var CancelVentaCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof CancelVentaCommand) {
            return CommandResult::failure(
                'Invalid command type. Expected CancelVentaCommand.'
            );
        }

        try {
            // Buscar la venta
            $venta = Venta::find($command->ventaId);

            if (!$venta) {
                return CommandResult::failure(
                    message: 'Venta no encontrada',
                    metadata: ['venta_id' => $command->ventaId]
                );
            }

            // Validar que no esté ya anulada
            if ($venta->estado_venta === 'anulada') {
                return CommandResult::failure(
                    message: 'La venta ya está anulada'
                );
            }

            // Anular la venta
            $ventaAnulada = $this->ventaService->anular($venta);

            Log::channel('audit')->info('Venta anulada via CQRS', [
                'venta_id' => $command->ventaId,
                'usuario_id' => $command->usuarioId,
                'motivo' => $command->motivo,
            ]);

            return CommandResult::success(
                id: $ventaAnulada->id,
                message: 'Venta anulada exitosamente',
                metadata: [
                    'motivo' => $command->motivo,
                    'estado' => $ventaAnulada->estado_venta,
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Error anulando venta via CQRS', [
                'error' => $e->getMessage(),
                'venta_id' => $command->ventaId,
            ]);

            return CommandResult::failure(
                message: $e->getMessage(),
            );
        }
    }
}
