<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class OrdenCompraUpdateDTO
{
    public function __construct(
        public readonly ?string $fecha_orden = null,
        public readonly ?string $fecha_entrega_esperada = null,
        public readonly ?string $estado = null,
        public readonly ?string $observaciones = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            fecha_orden: $request->filled('fecha_orden') ? (string) $request->input('fecha_orden') : null,
            fecha_entrega_esperada: $request->input('fecha_entrega_esperada'),
            estado: $request->filled('estado') ? (string) $request->input('estado') : null,
            observaciones: $request->filled('observaciones') ? trim((string) $request->input('observaciones')) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'fecha_orden' => $this->fecha_orden,
            'fecha_entrega_esperada' => $this->fecha_entrega_esperada,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ], fn ($value) => $value !== null);
    }
}
