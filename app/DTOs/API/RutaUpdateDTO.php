<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class RutaUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $origen = null,
        public readonly ?string $destino = null,
        public readonly ?float $distancia_km = null,
        public readonly ?int $duracion_estimada = null,
        public readonly ?float $tarifa_base = null,
        public readonly ?string $observaciones = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            origen: $request->filled('origen') ? $request->string('origen')->trim()->toString() : null,
            destino: $request->filled('destino') ? $request->string('destino')->trim()->toString() : null,
            distancia_km: $request->filled('distancia_km') ? (float) $request->input('distancia_km') : null,
            duracion_estimada: $request->filled('duracion_estimada') ? (int) $request->input('duracion_estimada') : null,
            tarifa_base: $request->filled('tarifa_base') ? (float) $request->input('tarifa_base') : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'distancia_km' => $this->distancia_km,
            'duracion_estimada' => $this->duracion_estimada,
            'tarifa_base' => $this->tarifa_base,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
