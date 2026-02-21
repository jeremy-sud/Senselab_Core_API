<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de clientes
 *
 * Valida y transforma datos de entrada para la creación de clientes
 * Fecha de creación: 12 de febrero de 2026
 */
final class ClienteCreateDTO
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
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            ciudad: $request->filled('ciudad') ? $request->string('ciudad')->trim()->toString() : null,
            provincia: $request->filled('provincia') ? $request->string('provincia')->trim()->toString() : null,
            razon_social: $request->filled('razon_social') ? $request->string('razon_social')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
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
            'activo' => $this->activo,
        ];
    }
}
