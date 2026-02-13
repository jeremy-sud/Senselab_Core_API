<?php

namespace App\DTOs\API\Producto;

use Illuminate\Http\Request;

/**
 * Data Transfer Object para creación de productos
 * 
 * Encapsula validación e hidratación de datos de entrada
 * para creación de nuevos productos
 */
final class ProductoCreateDTO
{
    /**
     * Constructor con typed properties
     */
    private function __construct(
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly float $precio,
        public readonly int $stock_inicial,
        public readonly int $categoria_id,
        public readonly ?string $codigo_externo = null,
        public readonly bool $activo = true,
    ) {}

    /**
     * Factory method para crear desde Request
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: trim($request->string('nombre')),
            descripcion: trim($request->string('descripcion')),
            precio: (float) $request->input('precio'),
            stock_inicial: (int) $request->input('stock_inicial', 0),
            categoria_id: (int) $request->input('categoria_id'),
            codigo_externo: $request->string('codigo_externo')?->toString() ?? null,
            activo: (bool) $request->input('activo', true),
        );
    }

    /**
     * Convertir DTO a array para creación de modelo
     */
    public function toModelData(): array
    {
        return [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio_venta' => $this->precio,
            'stock_actual' => $this->stock_inicial,
            'categoria_producto_id' => $this->categoria_id,
            'codigo_externo' => $this->codigo_externo,
            'activo' => $this->activo,
        ];
    }

    /**
     * Reglas de validación para este DTO
     */
    public static function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:productos,nombre',
            ],
            'descripcion' => [
                'required',
                'string',
                'max:1000',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'stock_inicial' => [
                'required',
                'integer',
                'min:0',
                'max:999999',
            ],
            'categoria_id' => [
                'required',
                'integer',
                'exists:categoria_productos,id',
            ],
            'codigo_externo' => [
                'nullable',
                'string',
                'max:50',
                'unique:productos,codigo_externo',
            ],
            'activo' => [
                'boolean',
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación
     */
    public static function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un producto con este nombre.',
            'codigo_externo.unique' => 'El código externo ya está registrado.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'precio.min' => 'El precio debe ser mayor a 0.',
        ];
    }
}
