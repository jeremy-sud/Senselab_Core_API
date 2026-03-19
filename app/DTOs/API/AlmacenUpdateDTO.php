<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class AlmacenUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $codigo = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $es_principal = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            codigo: $request->filled('codigo') ? $request->string('codigo')->trim()->toString() : null,
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            es_principal: $request->filled('es_principal') ? $request->boolean('es_principal') : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'es_principal' => $this->es_principal,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
