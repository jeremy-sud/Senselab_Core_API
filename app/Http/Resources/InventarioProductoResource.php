<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarioProductoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'almacen_id' => $this->almacen_id,
            'producto_id' => $this->producto_id,
            'stock_actual' => (float) $this->stock_actual,
            'costo_promedio' => (float) $this->costo_promedio,
            'stock_minimo' => (float) $this->stock_minimo,
            'stock_maximo' => (float) $this->stock_maximo,
            'ubicacion' => $this->ubicacion,
            'necesita_reposicion' => $this->necesitaReposicion(),
            'tiene_exceso' => $this->tieneExceso(),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            'almacen' => $this->whenLoaded('almacen', function () {
                return [
                    'id' => $this->almacen->id,
                    'nombre' => $this->almacen->nombre,
                ];
            }),
            
            'producto' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->nombre,
                    'codigo' => $this->producto->codigo ?? null,
                ];
            }),
        ];
    }
}
