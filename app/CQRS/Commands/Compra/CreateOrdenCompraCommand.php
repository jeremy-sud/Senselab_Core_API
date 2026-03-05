<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Compra;

use App\CQRS\Contracts\Command;

/**
 * Command para crear una orden de compra con detalles.
 */
final readonly class CreateOrdenCompraCommand implements Command
{
    /**
     * @param int $empresaId
     * @param int $proveedorId
     * @param string $fechaOrden
     * @param array<int, array{producto_id: int, cantidad: float, precio_unitario: float, descuento?: float, descripcion?: string}> $detalles
     * @param string|null $fechaEntregaEsperada
     * @param string $moneda
     * @param string|null $observaciones
     */
    public function __construct(
        public int $empresaId,
        public int $proveedorId,
        public string $fechaOrden,
        public array $detalles,
        public ?string $fechaEntregaEsperada = null,
        public string $moneda = 'CRC',
        public ?string $observaciones = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            empresaId: (int) $data['empresa_id'],
            proveedorId: (int) $data['proveedor_id'],
            fechaOrden: (string) $data['fecha_orden'],
            detalles: $data['detalles'] ?? [],
            fechaEntregaEsperada: $data['fecha_entrega_esperada'] ?? null,
            moneda: $data['moneda'] ?? 'CRC',
            observaciones: $data['observaciones'] ?? null,
        );
    }

    public function commandName(): string
    {
        return 'compras.crear_orden';
    }
}
