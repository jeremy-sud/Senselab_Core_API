<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class CargoCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
