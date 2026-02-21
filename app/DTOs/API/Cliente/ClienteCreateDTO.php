<?php

namespace App\DTOs\API\Cliente;

use Illuminate\Http\Request;

/**
 * DTO para creación de clientes
 */
final class ClienteCreateDTO
{
    private function __construct(
        public readonly string $nombre,
        public readonly string $identificacion,
        public readonly string $email,
        public readonly string $telefono,
        public readonly ?string $direccion = null,
        public readonly bool $es_empresa = false,
    ) {}

    /**
     * Factory desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: trim($request->string('nombre')),
            identificacion: trim($request->string('identificacion')),
            email: trim($request->string('email')),
            telefono: trim($request->string('telefono')),
            direccion: $request->input('direccion'),
            es_empresa: (bool) $request->input('es_empresa', false),
        );
    }

    /**
     * Convertir a array para modelo
     */
    /**
     * Convert the DTO to model data array.
     *
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        return [
            'nombre' => $this->nombre,
            'identificacion' => $this->identificacion,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'es_empresa' => $this->es_empresa,
        ];
    }

    /**
     * Validar reglas
     */
    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'identificacion' => ['required', 'string', 'max:50', 'unique:clientes,identificacion'],
            'email' => ['required', 'email', 'max:255', 'unique:clientes,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'es_empresa' => ['boolean'],
        ];
    }
}
