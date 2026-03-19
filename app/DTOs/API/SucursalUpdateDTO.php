<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class SucursalUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $codigo = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $provincia = null,
        public readonly ?string $canton = null,
        public readonly ?string $distrito = null,
        public readonly ?string $codigo_postal = null,
        public readonly ?bool $es_principal = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            codigo: $request->filled('codigo') ? $request->string('codigo')->trim()->toString() : null,
            email: $request->filled('email') ? $request->string('email')->trim()->toString() : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            provincia: $request->filled('provincia') ? $request->string('provincia')->trim()->toString() : null,
            canton: $request->filled('canton') ? $request->string('canton')->trim()->toString() : null,
            distrito: $request->filled('distrito') ? $request->string('distrito')->trim()->toString() : null,
            codigo_postal: $request->filled('codigo_postal') ? $request->string('codigo_postal')->trim()->toString() : null,
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
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'provincia' => $this->provincia,
            'canton' => $this->canton,
            'distrito' => $this->distrito,
            'codigo_postal' => $this->codigo_postal,
            'es_principal' => $this->es_principal,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
