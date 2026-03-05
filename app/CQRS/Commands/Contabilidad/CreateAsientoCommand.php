<?php

declare(strict_types=1);

namespace App\CQRS\Commands\Contabilidad;

use App\CQRS\Contracts\Command;

/**
 * Command para crear un asiento contable balanceado.
 */
final readonly class CreateAsientoCommand implements Command
{
    /**
     * @param int $empresaId
     * @param string $fecha
     * @param string $descripcion
     * @param array<int, array{cuenta_contable_id: int, debe: float, haber: float, referencia?: string}> $detalles
     * @param string|null $referencia
     * @param string|null $observaciones
     */
    public function __construct(
        public int $empresaId,
        public string $fecha,
        public string $descripcion,
        public array $detalles,
        public ?string $referencia = null,
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
            fecha: (string) $data['fecha'],
            descripcion: (string) $data['descripcion'],
            detalles: $data['detalles'] ?? [],
            referencia: $data['referencia'] ?? null,
            observaciones: $data['observaciones'] ?? null,
        );
    }

    public function commandName(): string
    {
        return 'contabilidad.crear_asiento';
    }
}
