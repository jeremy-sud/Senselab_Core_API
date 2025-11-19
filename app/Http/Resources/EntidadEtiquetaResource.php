<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntidadEtiquetaResource extends JsonResource
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
            'etiqueta_id' => $this->etiqueta_id,
            'entidad_tipo' => $this->entidad_tipo,
            'entidad_id' => $this->entidad_id,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relación con la etiqueta
            'etiqueta' => $this->whenLoaded('etiqueta', function () {
                return [
                    'id' => $this->etiqueta->id,
                    'nombre' => $this->etiqueta->nombre,
                    'color_hex' => $this->etiqueta->color_hex,
                ];
            }),
        ];
    }
}
