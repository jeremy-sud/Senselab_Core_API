<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class AlmacenCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $sucursal_id,
        public readonly string $nombre,
        public readonly ?string $codigo = null,
        public readonly ?string $descripcion = null,
        public readonly bool $es_principal = false,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            sucursal_id: (int) $request->input('sucursal_id'),
            nombre: $request->string('nombre')->trim()->toString(),
            codigo: $request->filled('codigo') ? $request->string('codigo')->trim()->toString() : null,
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            es_principal: $request->boolean('es_principal'),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'sucursal_id' => $this->sucursal_id,
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'es_principal' => $this->es_principal,
            'activo' => $this->activo,
        ];
    }
}
