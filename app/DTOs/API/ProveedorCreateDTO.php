<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de proveedores
 * 
 * Valida y transforma datos de entrada para la creación de proveedores
 * Fecha de creación: 12 de febrero de 2026
 */
final class ProveedorCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $cedula_juridica,
        public readonly string $email,
        public readonly string $telefono,
        public readonly int $empresa_id,
        public readonly ?string $direccion = null,
        public readonly ?string $ciudad = null,
        public readonly ?string $provincia = null,
        public readonly ?string $razon_social = null,
        public readonly ?string $contacto_principal = null,
        public readonly bool $activo = true,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim(),
            cedula_juridica: $request->string('cedula_juridica')->trim(),
            email: $request->string('email')->trim(),
            telefono: $request->string('telefono')->trim(),
            empresa_id: $request->integer('empresa_id'),
            direccion: $request->string('direccion')?->trim(),
            ciudad: $request->string('ciudad')?->trim(),
            provincia: $request->string('provincia')?->trim(),
            razon_social: $request->string('razon_social')?->trim(),
            contacto_principal: $request->string('contacto_principal')?->trim(),
            activo: $request->boolean('activo', true),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'cedula_juridica' => $this->cedula_juridica,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'empresa_id' => $this->empresa_id,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'provincia' => $this->provincia,
            'razon_social' => $this->razon_social,
            'contacto_principal' => $this->contacto_principal,
            'activo' => $this->activo,
        ];
    }
}
