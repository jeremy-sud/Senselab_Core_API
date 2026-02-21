<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Venta;

use App\CQRS\Contracts\Command;

/**
 * Command para crear una nueva venta.
 *
 * Encapsula todos los datos necesarios para registrar una venta en el sistema.
 *
 * @package App\CQRS\Commands\Venta
 * @author Sistemas Ursol S.A.
 */
final readonly class CreateVentaCommand implements Command
{
    /**
     * @param int $clienteId ID del cliente
     * @param int $sucursalId ID de la sucursal
     * @param int $usuarioId ID del usuario que registra
     * @param int $empresaId ID de la empresa (tenant)
     * @param array<int, array{producto_id: int, cantidad: float, precio_unitario: float, descuento?: float}> $detalles Líneas de la venta
     * @param string|null $formaPago Forma de pago (efectivo, tarjeta, etc.)
     * @param string|null $referencia Referencia externa
     * @param string|null $observaciones Observaciones adicionales
     * @param float|null $descuentoGlobal Descuento global sobre el total
     */
    public function __construct(
        public int $clienteId,
        public int $sucursalId,
        public int $usuarioId,
        public int $empresaId,
        public array $detalles,
        public ?string $formaPago = 'efectivo',
        public ?string $referencia = null,
        public ?string $observaciones = null,
        public ?float $descuentoGlobal = null,
    ) {}

    /**
     * Crea el comando desde un array de datos validados.
     *
     * @param array<string, mixed> $data Datos validados del request
     * @param int $usuarioId ID del usuario autenticado
     * @param int $empresaId ID de la empresa del tenant
     * @return self
     */
    public static function fromArray(array $data, int $usuarioId, int $empresaId): self
    {
        return new self(
            clienteId: (int) $data['cliente_id'],
            sucursalId: (int) $data['sucursal_id'],
            usuarioId: $usuarioId,
            empresaId: $empresaId,
            detalles: $data['detalles'] ?? [],
            formaPago: $data['forma_pago'] ?? 'efectivo',
            referencia: $data['referencia'] ?? null,
            observaciones: $data['observaciones'] ?? null,
            descuentoGlobal: isset($data['descuento_global']) ? (float) $data['descuento_global'] : null,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function commandName(): string
    {
        return 'venta.create';
    }

    /**
     * Calcula el total de líneas de detalle.
     *
     * @return int
     */
    public function countDetalles(): int
    {
        return count($this->detalles);
    }
}
