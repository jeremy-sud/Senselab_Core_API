<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Venta;

use App\CQRS\Contracts\Command;
use App\CQRS\Contracts\CommandHandler;
use App\CQRS\Contracts\CommandResult;
use App\DTOs\API\VentaCreateDTO;
use App\Services\VentaService;
use Illuminate\Support\Facades\Log;

/**
 * Handler para el comando CreateVentaCommand.
 *
 * Orquesta la creación de una venta delegando la lógica de negocio
 * al VentaService existente.
 *
 * @package App\CQRS\Commands\Venta
 * @author Sistemas Ursol S.A.
 */
final class CreateVentaCommandHandler implements CommandHandler
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
     * @param CreateVentaCommand $command
     */
    public function handle(Command $command): CommandResult
    {
        /** @var CreateVentaCommand $command */
        /** @phpstan-ignore instanceof.alwaysTrue */
        if (!$command instanceof CreateVentaCommand) {
            return CommandResult::failure(
                'Invalid command type. Expected CreateVentaCommand.'
            );
        }

        try {
            // Convertir Command a DTO para el service
            $dto = new VentaCreateDTO(
                cliente_id: $command->clienteId,
                empresa_id: $command->empresaId,
                sucursal_id: $command->sucursalId,
                usuario_id: $command->usuarioId,
                fecha_venta: now()->format('Y-m-d'),
                tipo_comprobante: 'factura',
                observaciones: $command->observaciones,
                detalles: $command->detalles,
            );

            // Delegar al service existente
            $venta = $this->ventaService->crear($dto);

            Log::channel('audit')->info('Venta creada via CQRS', [
                'venta_id' => $venta->id,
                'cliente_id' => $command->clienteId,
                'total' => $venta->monto_total_venta,
                'detalles_count' => $command->countDetalles(),
            ]);

            return CommandResult::success(
                id: $venta->id,
                message: 'Venta creada exitosamente',
                metadata: [
                    'numero_comprobante' => $venta->numero_comprobante,
                    'total' => $venta->monto_total_venta,
                    'estado' => $venta->estado,
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return CommandResult::failure(
                message: 'Error de validación al crear venta',
                errors: $e->errors(),
            );
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Error creando venta via CQRS', [
                'error' => $e->getMessage(),
                'cliente_id' => $command->clienteId,
            ]);

            return CommandResult::failure(
                message: $e->getMessage(),
            );
        }
    }
}
