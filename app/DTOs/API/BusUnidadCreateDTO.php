<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class BusUnidadCreateDTO
{
    public function __construct(
        public readonly string $placa,
        public readonly int $capacidad_asientos,
        public readonly ?int $modelo_id = null,
        public readonly ?string $identificador_interno = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            placa: $request->string('placa')->trim()->toString(),
            capacidad_asientos: (int) $request->input('capacidad_asientos'),
            modelo_id: $request->filled('modelo_id') ? (int) $request->input('modelo_id') : null,
            identificador_interno: $request->filled('identificador_interno') ? $request->string('identificador_interno')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'placa' => $this->placa,
            'modelo_id' => $this->modelo_id,
            'capacidad_asientos' => $this->capacidad_asientos,
            'identificador_interno' => $this->identificador_interno,
            'activo' => $this->activo,
        ];
    }
}
