<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ZonaGeograficaCreateDTO
{
    /**
     * @param array<int, string>|null $provincias_incluidas
     */
    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $tipo,
        public readonly ?int $zona_padre_id = null,
        public readonly ?array $provincias_incluidas = null,
        public readonly ?int $vendedor_asignado_id = null,
        public readonly bool $activa = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo: $request->string('codigo')->trim()->toString(),
            nombre: $request->string('nombre')->trim()->toString(),
            tipo: $request->string('tipo')->trim()->toString(),
            zona_padre_id: $request->filled('zona_padre_id') ? (int) $request->input('zona_padre_id') : null,
            provincias_incluidas: $request->has('provincias_incluidas') ? (array) $request->input('provincias_incluidas') : null,
            vendedor_asignado_id: $request->filled('vendedor_asignado_id') ? (int) $request->input('vendedor_asignado_id') : null,
            activa: $request->boolean('activa', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'zona_padre_id' => $this->zona_padre_id,
            'provincias_incluidas' => $this->provincias_incluidas,
            'vendedor_asignado_id' => $this->vendedor_asignado_id,
            'activa' => $this->activa,
        ];
    }
}
