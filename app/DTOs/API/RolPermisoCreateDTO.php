<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class RolPermisoCreateDTO
{
    public function __construct(
        public readonly int $rol_id,
        public readonly int $permiso_id,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            rol_id: (int) $request->input('rol_id'),
            permiso_id: (int) $request->input('permiso_id'),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'rol_id' => $this->rol_id,
            'permiso_id' => $this->permiso_id,
            'activo' => $this->activo,
        ];
    }
}
