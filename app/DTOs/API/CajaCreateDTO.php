<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class CajaCreateDTO
{
    public function __construct(
        public readonly int $sucursal_id,
        public readonly ?int $usuario_id = null,
        public readonly string $nombre = '',
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            sucursal_id: (int) $request->input('sucursal_id'),
            usuario_id: $request->filled('usuario_id') ? (int) $request->input('usuario_id') : null,
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sucursal_id' => $this->sucursal_id,
            'usuario_id' => $this->usuario_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
