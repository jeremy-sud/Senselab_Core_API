<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class FeCertificadoDigitalCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $fecha_vencimiento,
        public readonly string $ambiente,
        public readonly ?string $numero_serie = null,
        public readonly ?string $emisor = null,
        public readonly ?string $sujeto = null,
        public readonly ?string $fecha_emision = null,
        public readonly bool $activo = true,
        public readonly ?string $observaciones = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            fecha_vencimiento: $request->string('fecha_vencimiento')->trim()->toString(),
            ambiente: $request->string('ambiente')->trim()->toString(),
            numero_serie: $request->filled('numero_serie') ? $request->string('numero_serie')->trim()->toString() : null,
            emisor: $request->filled('emisor') ? $request->string('emisor')->trim()->toString() : null,
            sujeto: $request->filled('sujeto') ? $request->string('sujeto')->trim()->toString() : null,
            fecha_emision: $request->filled('fecha_emision') ? $request->string('fecha_emision')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'ambiente' => $this->ambiente,
            'numero_serie' => $this->numero_serie,
            'emisor' => $this->emisor,
            'sujeto' => $this->sujeto,
            'fecha_emision' => $this->fecha_emision,
            'activo' => $this->activo,
            'observaciones' => $this->observaciones,
        ];
    }
}
