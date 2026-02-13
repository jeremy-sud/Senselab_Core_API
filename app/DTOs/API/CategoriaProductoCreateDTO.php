<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de categoría de producto
 * 
 * Valida y transforma datos de entrada para la creación de categorías de productos
 * Fecha de creación: 12 de febrero de 2026
 */
final class CategoriaProductoCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly int $empresa_id,
        public readonly ?string $descripcion = null,
        public readonly ?int $categoria_padre_id = null,
        public readonly bool $activo = true,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim(),
            empresa_id: $request->integer('empresa_id'),
            descripcion: $request->string('descripcion')?->trim(),
            categoria_padre_id: $request->integer('categoria_padre_id'),
            activo: $request->boolean('activo', true),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'nombre' => $this->nombre,
            'empresa_id' => $this->empresa_id,
            'descripcion' => $this->descripcion,
            'categoria_padre_id' => $this->categoria_padre_id,
            'activo' => $this->activo,
        ];
    }
}
