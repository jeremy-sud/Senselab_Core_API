<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'codigo' => $this->codigo,
            'codigo_barras' => $this->codigo_barras,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('categoria', function () {
                return [
                    'id' => $this->categoria->id,
                    'nombre' => $this->categoria->nombre,
                ];
            }),
            'marca_id' => $this->marca_id,
            'marca' => $this->whenLoaded('marca', function () {
                return [
                    'id' => $this->marca->id,
                    'nombre' => $this->marca->nombre,
                ];
            }),
            'unidad_medida_id' => $this->unidad_medida_id,
            'unidad_medida' => $this->whenLoaded('unidadMedida', function () {
                return [
                    'id' => $this->unidadMedida->id,
                    'nombre' => $this->unidadMedida->nombre,
                    'simbolo' => $this->unidadMedida->simbolo,
                ];
            }),
            'precio_costo' => (float) $this->precio_costo,
            'precio_venta' => (float) $this->precio_venta,
            'precio_mayoreo' => $this->precio_mayoreo ? (float) $this->precio_mayoreo : null,
            'stock_minimo' => (int) $this->stock_minimo,
            'stock_maximo' => (int) $this->stock_maximo,
            'tipo_impuesto_id' => $this->tipo_impuesto_id,
            'tipo_impuesto' => $this->whenLoaded('tipoImpuesto', function () {
                return [
                    'id' => $this->tipoImpuesto->id,
                    'nombre' => $this->tipoImpuesto->nombre,
                    'porcentaje' => (float) $this->tipoImpuesto->porcentaje,
                ];
            }),
            'exento_impuesto' => (bool) $this->exento_impuesto,
            'tipo_producto' => $this->tipo_producto,
            'imagen_url' => $this->imagen_url,
            'activo' => (bool) $this->activo,
            'gestionado_inventario' => (bool) $this->gestionado_inventario,
            
            // Stock total (si está cargado)
            'stock_total' => $this->when(
                $this->relationLoaded('inventarios'),
                fn() => $this->inventarios->sum('cantidad_disponible')
            ),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
