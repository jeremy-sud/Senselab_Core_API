<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class UnidadMedidaCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $abreviatura,
        public readonly ?string $codigo_oficial = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            abreviatura: $request->string('abreviatura')->trim()->toString(),
            codigo_oficial: $request->filled('codigo_oficial') ? $request->string('codigo_oficial')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'abreviatura' => $this->abreviatura,
            'codigo_oficial' => $this->codigo_oficial,
            'activo' => $this->activo,
        ];
    }
}
