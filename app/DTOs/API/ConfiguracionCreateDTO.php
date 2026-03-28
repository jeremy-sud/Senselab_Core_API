<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ConfiguracionCreateDTO
{
    public function __construct(
        public readonly string $clave,
        public readonly string $valor,
        public readonly string $tipo_dato,
        public readonly ?string $descripcion = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            clave: $request->string('clave')->trim()->toString(),
            valor: $request->string('valor')->trim()->toString(),
            tipo_dato: $request->string('tipo_dato')->trim()->toString(),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'clave' => $this->clave,
            'valor' => $this->valor,
            'tipo_dato' => $this->tipo_dato,
            'descripcion' => $this->descripcion,
        ];
    }
}
