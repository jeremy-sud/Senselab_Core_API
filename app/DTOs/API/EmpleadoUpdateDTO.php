<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class EmpleadoUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $primer_apellido = null,
        public readonly ?string $segundo_apellido = null,
        public readonly ?string $tipo_documento = null,
        public readonly ?string $numero_documento = null,
        public readonly ?string $fecha_nacimiento = null,
        public readonly ?string $fecha_ingreso = null,
        public readonly ?int $cargo_id = null,
        public readonly ?float $salario = null,
        public readonly ?string $direccion = null,
        public readonly ?string $telefono = null,
        public readonly ?string $email = null,
        public readonly ?bool $activo = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim()->toString() : null,
            primer_apellido: $request->filled('primer_apellido') ? $request->string('primer_apellido')->trim()->toString() : null,
            segundo_apellido: $request->filled('segundo_apellido') ? $request->string('segundo_apellido')->trim()->toString() : null,
            tipo_documento: $request->filled('tipo_documento') ? (string) $request->input('tipo_documento') : null,
            numero_documento: $request->filled('numero_documento') ? $request->string('numero_documento')->trim()->toString() : null,
            fecha_nacimiento: $request->input('fecha_nacimiento'),
            fecha_ingreso: $request->filled('fecha_ingreso') ? (string) $request->input('fecha_ingreso') : null,
            cargo_id: $request->filled('cargo_id') ? (int) $request->input('cargo_id') : null,
            salario: $request->filled('salario') ? (float) $request->input('salario') : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            email: $request->filled('email') ? $request->string('email')->trim()->toString() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'primer_apellido' => $this->primer_apellido,
            'segundo_apellido' => $this->segundo_apellido,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'fecha_ingreso' => $this->fecha_ingreso,
            'cargo_id' => $this->cargo_id,
            'salario' => $this->salario,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'activo' => $this->activo,
        ], fn ($value) => $value !== null);
    }
}
