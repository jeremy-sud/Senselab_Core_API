<?php

namespace App\DTOs\API\Producto;

use Illuminate\Http\Request;

/**
 * Data Transfer Object para actualización de productos
 *
 * Similar a ProductoCreateDTO pero con campos opcionales
 * para permitir actualizaciones parciales
 */
final class ProductoUpdateDTO
{
    private function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $descripcion = null,
        public readonly ?float $precio = null,
        public readonly ?int $categoria_id = null,
        public readonly ?string $codigo_externo = null,
        public readonly ?bool $activo = null,
    ) {}

    /**
     * Factory method para crear desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->input('nombre'),
            descripcion: $request->input('descripcion'),
            precio: $request->has('precio') ? (float) $request->input('precio') : null,
            categoria_id: $request->has('categoria_id') ? (int) $request->input('categoria_id') : null,
            codigo_externo: $request->input('codigo_externo'),
            activo: $request->has('activo') ? (bool) $request->input('activo') : null,
        );
    }

    /**
     * Convertir DTO a array filtrado (solo cambios)
     */
    public function toModelData(): array
    {
        $data = [];

        if ($this->nombre !== null) {
            $data['nombre'] = $this->nombre;
        }
        if ($this->descripcion !== null) {
            $data['descripcion'] = $this->descripcion;
        }
        if ($this->precio !== null) {
            $data['precio_venta'] = $this->precio;
        }
        if ($this->categoria_id !== null) {
            $data['categoria_producto_id'] = $this->categoria_id;
        }
        if ($this->codigo_externo !== null) {
            $data['codigo_externo'] = $this->codigo_externo;
        }
        if ($this->activo !== null) {
            $data['activo'] = $this->activo;
        }

        return $data;
    }

    /**
     * Reglas de validación (todos opcionales)
     */
    public static function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'unique:productos,nombre',
            ],
            'descripcion' => [
                'sometimes',
                'required',
                'string',
                'max:1000',
            ],
            'precio' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
            'categoria_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:categoria_productos,id',
            ],
            'codigo_externo' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                'unique:productos,codigo_externo',
            ],
            'activo' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
