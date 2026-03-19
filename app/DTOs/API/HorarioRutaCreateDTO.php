<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class HorarioRutaCreateDTO
{
    public function __construct(
        public readonly int $ruta_id,
        public readonly int $bus_id,
        public readonly string $fecha_salida,
        public readonly string $hora_salida,
        public readonly ?string $fecha_llegada_estimada = null,
        public readonly ?string $hora_llegada_estimada = null,
        public readonly ?string $estado = 'Programado',
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            ruta_id: (int) $request->input('ruta_id'),
            bus_id: (int) $request->input('bus_id'),
            fecha_salida: (string) $request->input('fecha_salida'),
            hora_salida: (string) $request->input('hora_salida'),
            fecha_llegada_estimada: $request->input('fecha_llegada_estimada'),
            hora_llegada_estimada: $request->input('hora_llegada_estimada'),
            estado: (string) $request->input('estado', 'Programado'),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'ruta_id' => $this->ruta_id,
            'bus_id' => $this->bus_id,
            'fecha_salida' => $this->fecha_salida,
            'hora_salida' => $this->hora_salida,
            'fecha_llegada_estimada' => $this->fecha_llegada_estimada,
            'hora_llegada_estimada' => $this->hora_llegada_estimada,
            'estado' => $this->estado,
            'activo' => $this->activo,
        ];
    }
}
