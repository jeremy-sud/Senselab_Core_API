<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlmacenResource extends JsonResource
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
            'sucursal_id' => $this->sucursal_id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'ubicacion' => $this->ubicacion,
            'tipo_almacen' => $this->tipo_almacen,
            'activo' => (bool) $this->activo,
            
            // Relaciones
            'sucursal' => $this->whenLoaded('sucursal', function () {
                return [
                    'id' => $this->sucursal->id,
                    'nombre' => $this->sucursal->nombre,
                    'codigo' => $this->sucursal->codigo,
                ];
            }),
            
            // Estadísticas de inventario
            'inventarios_count' => $this->whenCounted('inventarios'),
            'valor_total_inventario' => $this->when(
                $this->relationLoaded('inventarios'),
                function () {
                    return $this->inventarios->sum(function ($inv) {
                        return $inv->cantidad_disponible * $inv->producto->precio_costo;
                    });
                }
            ),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
