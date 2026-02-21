<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de cuenta contable
 *
 * Valida y transforma datos de entrada para la creación de cuentas contables
 * Fecha de creación: 12 de febrero de 2026
 */
final class CuentaContableCreateDTO
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly int $empresa_id,
        public readonly int $tipo_cuenta_id,
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo: $request->string('codigo')->trim(),
            nombre: $request->string('nombre')->trim(),
            empresa_id: $request->integer('empresa_id'),
            tipo_cuenta_id: $request->integer('tipo_cuenta_id'),
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
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
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'empresa_id' => $this->empresa_id,
            'tipo_cuenta_id' => $this->tipo_cuenta_id,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
