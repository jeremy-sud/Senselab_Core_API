<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de productos
 *
 * Valida y transforma datos de entrada para la creación de productos
 * Fecha de creación: 12 de febrero de 2026
 */
final class ProductoCreateDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly string $descripcion,
        public readonly float $precio,
        public readonly int $stock_inicial,
        public readonly int $categoria_id,
        public readonly int $empresa_id,
        public readonly ?string $sku = null,
        public readonly ?string $codigo_interno = null,
        public readonly bool $activo = true,
        public readonly ?float $precio_costo = null,
        public readonly ?string $unidad_medida = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            nombre: $request->string('nombre')->trim(),
            descripcion: $request->string('descripcion')->trim(),
            precio: $request->float('precio'),
            stock_inicial: $request->integer('stock_inicial'),
            categoria_id: $request->integer('categoria_id'),
            empresa_id: $request->integer('empresa_id'),
            sku: $request->filled('sku') ? $request->string('sku')->trim()->toString() : null,
            codigo_interno: $request->filled('codigo_interno') ? $request->string('codigo_interno')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
            precio_costo: $request->float('precio_costo'),
            unidad_medida: $request->filled('unidad_medida') ? $request->string('unidad_medida')->trim()->toString() : null,
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
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'stock_inicial' => $this->stock_inicial,
            'categoria_id' => $this->categoria_id,
            'empresa_id' => $this->empresa_id,
            'sku' => $this->sku,
            'codigo_interno' => $this->codigo_interno,
            'activo' => $this->activo,
            'precio_costo' => $this->precio_costo,
            'unidad_medida' => $this->unidad_medida,
        ];
    }
}
