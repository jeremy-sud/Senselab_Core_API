<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtiquetaResource extends JsonResource
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
            'nombre' => $this->nombre,
            'color_hex' => $this->color_hex,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Contador de usos
            'veces_usada' => $this->whenCounted('entidadEtiquetas'),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa'),
            'entidad_etiquetas' => EntidadEtiquetaResource::collection($this->whenLoaded('entidadEtiquetas')),
        ];
    }
}
