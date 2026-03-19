<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class RutaCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $origen,
        public readonly string $destino,
        public readonly float $tarifa_base,
        public readonly ?float $distancia_km = null,
        public readonly ?int $duracion_estimada = null,
        public readonly ?string $observaciones = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            origen: $request->string('origen')->trim()->toString(),
            destino: $request->string('destino')->trim()->toString(),
            tarifa_base: (float) $request->input('tarifa_base'),
            distancia_km: $request->filled('distancia_km') ? (float) $request->input('distancia_km') : null,
            duracion_estimada: $request->filled('duracion_estimada') ? (int) $request->input('duracion_estimada') : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'distancia_km' => $this->distancia_km,
            'duracion_estimada' => $this->duracion_estimada,
            'tarifa_base' => $this->tarifa_base,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
        ];
    }
}
