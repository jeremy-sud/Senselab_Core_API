<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class TipoImpuestoCreateDTO
{
    public function __construct(
        public readonly string $codigo_hacienda,
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly ?string $comentario = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo_hacienda: $request->string('codigo_hacienda')->trim()->toString(),
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            comentario: $request->filled('comentario') ? $request->string('comentario')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'codigo_hacienda' => $this->codigo_hacienda,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'comentario' => $this->comentario,
            'activo' => $this->activo,
        ];
    }
}
