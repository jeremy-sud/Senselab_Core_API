<?php

namespace App\DTOs\API\Contabilidad;

use Illuminate\Http\Request;

/**
 * CuentaContableCreateDTO - DTO para crear cuentas contables
 * 
 * Define estructura del plan de cuentas del sistema.
 */
final class CuentaContableCreateDTO
{
    private function __construct(
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $tipo_cuenta, // activo, pasivo, patrimonio, ingreso, gasto
        public readonly string $subcategoria, // circulante, fijo, etc
        public readonly ?string $descripcion = null,
        public readonly bool $activa = true,
        public readonly ?int $cuenta_padre_id = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            codigo: trim($request->input('codigo')),
            nombre: trim($request->input('nombre')),
            tipo_cuenta: $request->input('tipo_cuenta'),
            subcategoria: $request->input('subcategoria'),
            descripcion: $request->input('descripcion'),
            activa: (bool) $request->input('activa', true),
            cuenta_padre_id: $request->input('cuenta_padre_id') !== null
                ? (int) $request->input('cuenta_padre_id')
                : null,
        );
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo_cuenta' => $this->tipo_cuenta,
            'subcategoria' => $this->subcategoria,
            'descripcion' => $this->descripcion,
            'activa' => $this->activa,
            'cuenta_padre_id' => $this->cuenta_padre_id,
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'codigo' => 'required|string|max:50|unique:cuentas_contables,codigo',
            'nombre' => 'required|string|max:255',
            'tipo_cuenta' => 'required|in:activo,pasivo,patrimonio,ingreso,gasto',
            'subcategoria' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'activa' => 'boolean',
            'cuenta_padre_id' => 'nullable|integer|exists:cuentas_contables,id',
        ];
    }

    public static function messages(): array
    {
        return [
            'codigo.unique' => 'El código de cuenta ya existe',
            'tipo_cuenta.in' => 'Tipo de cuenta inválido',
        ];
    }
}
