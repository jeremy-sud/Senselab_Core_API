<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class TipoClienteCreateDTO
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly ?float $descuento_default = null,
        public readonly ?int $dias_credito_default = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo: $request->string('codigo')->trim()->toString(),
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            descuento_default: $request->filled('descuento_default') ? (float) $request->input('descuento_default') : null,
            dias_credito_default: $request->filled('dias_credito_default') ? (int) $request->input('dias_credito_default') : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'descuento_default' => $this->descuento_default,
            'dias_credito_default' => $this->dias_credito_default,
            'activo' => $this->activo,
        ];
    }
}
