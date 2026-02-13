<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para actualización de productos
 *
 * Valida y transforma datos de entrada para la actualización de productos
 * Fecha de creación: 12 de febrero de 2026
 */
final class ProductoUpdateDTO
{
    public function __construct(
        public readonly ?string $nombre = null,
        public readonly ?string $descripcion = null,
        public readonly ?float $precio = null,
        public readonly ?int $categoria_id = null,
        public readonly ?string $sku = null,
        public readonly ?string $codigo_interno = null,
        public readonly ?bool $activo = null,
        public readonly ?float $precio_costo = null,
        public readonly ?string $unidad_medida = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->filled('nombre') ? $request->string('nombre')->trim() : null,
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim() : null,
            precio: $request->filled('precio') ? $request->float('precio') : null,
            categoria_id: $request->filled('categoria_id') ? $request->integer('categoria_id') : null,
            sku: $request->filled('sku') ? $request->string('sku')->trim() : null,
            codigo_interno: $request->filled('codigo_interno') ? $request->string('codigo_interno')->trim() : null,
            activo: $request->filled('activo') ? $request->boolean('activo') : null,
            precio_costo: $request->filled('precio_costo') ? $request->float('precio_costo') : null,
            unidad_medida: $request->filled('unidad_medida') ? $request->string('unidad_medida')->trim() : null,
        );
    }

    /**
     * Convertir a array para actualizar en base de datos
     * Solo incluye campos que no son null
     */
    public function toArray(): array
    {
        return array_filter([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'categoria_id' => $this->categoria_id,
            'sku' => $this->sku,
            'codigo_interno' => $this->codigo_interno,
            'activo' => $this->activo,
            'precio_costo' => $this->precio_costo,
            'unidad_medida' => $this->unidad_medida,
        ], function ($value) {
            return $value !== null;
        });
    }
}
