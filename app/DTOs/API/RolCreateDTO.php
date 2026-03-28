<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class RolCreateDTO
{
    /**
     * @param array<int, int>|null $permisos
     */
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
        public readonly ?array $permisos = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
            permisos: $request->has('permisos') ? array_map('intval', (array) $request->input('permisos')) : null,
        );
    }

    /**
     * @return array<int, int>|null
     */
    public function getPermisos(): ?array
    {
        return $this->permisos;
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
