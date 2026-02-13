<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para actualización de clientes
 * 
 * Valida y transforma datos de entrada para la actualización de clientes
 * Fecha de creación: 12 de febrero de 2026
 */
final class ClienteUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $email = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?string $ciudad = null,
        public readonly ?string $provincia = null,
        public readonly ?string $razon_social = null,
        public readonly ?bool $activo = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim() : null,
            email: $request->filled('email') ? $request->string('email')->trim() : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim() : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim() : null,
            ciudad: $request->filled('ciudad') ? $request->string('ciudad')->trim() : null,
            provincia: $request->filled('provincia') ? $request->string('provincia')->trim() : null,
            razon_social: $request->filled('razon_social') ? $request->string('razon_social')->trim() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /**
     * Convertir a array para actualizar en base de datos
     */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'provincia' => $this->provincia,
            'razon_social' => $this->razon_social,
            'activo' => $this->activo,
        ], function ($value) {
            return $value !== null;
        });
    }
}
