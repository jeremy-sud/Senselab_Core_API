<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class UsuarioCreateDTO
{
    /**
     * @param array<int, int> $roles
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $apellidos,
        public readonly string $email,
        public readonly string $password,
        public readonly ?int $cargo_id = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly bool $activo = true,
        public readonly array $roles = [],
    ) {}

    /**
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            apellidos: $request->string('apellidos')->trim()->toString(),
            email: $request->string('email')->trim()->toString(),
            password: (string) $request->input('password'),
            cargo_id: $request->filled('cargo_id') ? (int) $request->input('cargo_id') : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
            roles: $request->input('roles', []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'password' => $this->password,
            'cargo_id' => $this->cargo_id,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'activo' => $this->activo,
        ];
    }

    /** @return array<int, int> */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
