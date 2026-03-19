<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class EmpleadoCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $primer_apellido,
        public readonly string $tipo_documento,
        public readonly string $numero_documento,
        public readonly float $salario,
        public readonly ?string $segundo_apellido = null,
        public readonly ?string $fecha_nacimiento = null,
        public readonly ?string $fecha_ingreso = null,
        public readonly ?int $cargo_id = null,
        public readonly ?string $direccion = null,
        public readonly ?string $telefono = null,
        public readonly ?string $email = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim()->toString(),
            primer_apellido: $request->string('primer_apellido')->trim()->toString(),
            tipo_documento: (string) $request->input('tipo_documento'),
            numero_documento: $request->string('numero_documento')->trim()->toString(),
            salario: (float) $request->input('salario'),
            segundo_apellido: $request->filled('segundo_apellido') ? $request->string('segundo_apellido')->trim()->toString() : null,
            fecha_nacimiento: $request->input('fecha_nacimiento'),
            fecha_ingreso: $request->input('fecha_ingreso'),
            cargo_id: $request->filled('cargo_id') ? (int) $request->input('cargo_id') : null,
            direccion: $request->filled('direccion') ? $request->string('direccion')->trim()->toString() : null,
            telefono: $request->filled('telefono') ? $request->string('telefono')->trim()->toString() : null,
            email: $request->filled('email') ? $request->string('email')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
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
        ];
    }
}
