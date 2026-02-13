<?php

namespace App\DTOs\API\Producto;

use Illuminate\Http\Request;

/**
 * CategoriaProductoCreateDTO - DTO para crear categorías de productos
 *
 * Organiza productos en categorías jerárquicas para mejor clasificación.
 */
final class CategoriaProductoCreateDTO
{
    private function __construct(
        public readonly string $nombre,
        public readonly ?int $categoria_padre_id = null,
        public readonly string $descripcion = '',
        public readonly ?string $codigo = null,
        public readonly bool $activa = true,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: trim($request->input('nombre')),
            categoria_padre_id: $request->input('categoria_padre_id') !== null
                ? (int) $request->input('categoria_padre_id')
                : null,
            descripcion: $request->input('descripcion', ''),
            codigo: $request->input('codigo'),
            activa: (bool) $request->input('activa', true),
        );
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'nombre' => $this->nombre,
            'categoria_padre_id' => $this->categoria_padre_id,
            'descripcion' => $this->descripcion,
            'codigo' => $this->codigo,
            'activa' => $this->activa,
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:categorias_productos,nombre',
            'categoria_padre_id' => 'nullable|integer|exists:categorias_productos,id',
            'descripcion' => 'nullable|string|max:1000',
            'codigo' => 'nullable|string|max:50|unique:categorias_productos,codigo',
            'activa' => 'boolean',
        ];
    }

    public static function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es requerido',
            'nombre.unique' => 'La categoría ya existe',
            'codigo.unique' => 'El código ya existe',
        ];
    }
}
