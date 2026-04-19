<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class RolUsuarioCreateDTO
{
    public function __construct(
        public readonly int $usuario_id,
        public readonly int $rol_id,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            usuario_id: (int) $request->input('usuario_id'),
            rol_id: (int) $request->input('rol_id'),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'usuario_id' => $this->usuario_id,
            'rol_id' => $this->rol_id,
            'activo' => $this->activo,
        ];
    }
}
