<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class FormaPagoCreateDTO
{
    public function __construct(
        public readonly string $codigo_dgt,
        public readonly string $nombre,
        public readonly ?string $descripcion = null,
        public readonly ?string $tipo = null,
        public readonly bool $requiere_referencia = false,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo_dgt: $request->string('codigo_dgt')->trim()->toString(),
            nombre: $request->string('nombre')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            tipo: $request->filled('tipo') ? $request->string('tipo')->trim()->toString() : null,
            requiere_referencia: $request->boolean('requiere_referencia', false),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'codigo_dgt' => $this->codigo_dgt,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo' => $this->tipo,
            'requiere_referencia' => $this->requiere_referencia,
            'activo' => $this->activo,
        ];
    }
}
