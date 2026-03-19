<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class UsuarioUpdateDTO
{
    /**
     * @param array<int, int>|null $roles
     */
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $apellidos = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?int $cargo_id = null,
        public readonly ?string $telefono = null,
        public readonly ?string $direccion = null,
        public readonly ?bool $activo = null,
        public readonly ?array $roles = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            apellidos: $request->filled('apellidos') ? $request->string('apellidos')->trim()->toString() : null,
            email: $request->filled('email') ? $request->string('email')->trim()->toString() : null,
            password: $request->filled('password') ? (string) $request->input('password') : null,
            cargo_id: $request->filled('cargo_id') ? (int) $request->input('cargo_id') : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
            roles: $request->has('roles') ? $request->input('roles') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'email' => $this->email,
            'password' => $this->password,
            'cargo_id' => $this->cargo_id,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }

    /** @return array<int, int>|null */
    public function getRoles(): ?array
    {
        return $this->roles;
    }
}
