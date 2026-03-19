<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class BusUnidadUpdateDTO
{
    public function __construct(
        public readonly ?string $placa = null,
        public readonly ?int $modelo_id = null,
        public readonly ?int $capacidad_asientos = null,
        public readonly ?string $identificador_interno = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            placa: $request->filled('placa') ? $request->string('placa')->trim()->toString() : null,
            modelo_id: $request->filled('modelo_id') ? (int) $request->input('modelo_id') : null,
            capacidad_asientos: $request->filled('capacidad_asientos') ? (int) $request->input('capacidad_asientos') : null,
            identificador_interno: $request->filled('identificador_interno') ? $request->string('identificador_interno')->trim()->toString() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'placa' => $this->placa,
            'modelo_id' => $this->modelo_id,
            'capacidad_asientos' => $this->capacidad_asientos,
            'identificador_interno' => $this->identificador_interno,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
