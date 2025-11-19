<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaResource extends JsonResource
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
            'sucursal_id' => $this->sucursal_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'sucursal' => $this->whenLoaded('sucursal', function () {
                return [
                    'id' => $this->sucursal->id,
                    'nombre' => $this->sucursal->nombre,
                    'codigo' => $this->sucursal->codigo ?? null,
                ];
            }),
        ];
    }
}
