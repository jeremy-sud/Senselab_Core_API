<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class HorarioRutaUpdateDTO
{
    public function __construct(
        public readonly ?int $ruta_id = null,
        public readonly ?int $bus_id = null,
        public readonly ?string $fecha_salida = null,
        public readonly ?string $hora_salida = null,
        public readonly ?string $fecha_llegada_estimada = null,
        public readonly ?string $hora_llegada_estimada = null,
        public readonly ?string $estado = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            ruta_id: $request->filled('ruta_id') ? (int) $request->input('ruta_id') : null,
            bus_id: $request->filled('bus_id') ? (int) $request->input('bus_id') : null,
            fecha_salida: $request->filled('fecha_salida') ? (string) $request->input('fecha_salida') : null,
            hora_salida: $request->filled('hora_salida') ? (string) $request->input('hora_salida') : null,
            fecha_llegada_estimada: $request->input('fecha_llegada_estimada'),
            hora_llegada_estimada: $request->input('hora_llegada_estimada'),
            estado: $request->filled('estado') ? (string) $request->input('estado') : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'ruta_id' => $this->ruta_id,
            'bus_id' => $this->bus_id,
            'fecha_salida' => $this->fecha_salida,
            'hora_salida' => $this->hora_salida,
            'fecha_llegada_estimada' => $this->fecha_llegada_estimada,
            'hora_llegada_estimada' => $this->hora_llegada_estimada,
            'estado' => $this->estado,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
