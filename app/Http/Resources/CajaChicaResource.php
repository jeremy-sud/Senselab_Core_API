<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CajaChicaResource extends JsonResource
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
            'fecha' => $this->fecha,
            'descripcion' => $this->descripcion,
            'monto' => (float) $this->monto,
            'tipo' => $this->tipo,
            'responsable_id' => $this->responsable_id,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'responsable' => $this->whenLoaded('responsable', function () {
                return [
                    'id' => $this->responsable->id,
                    'nombre' => $this->responsable->name,
                    'email' => $this->responsable->email,
                ];
            }),
            
            'empresa' => $this->whenLoaded('empresa'),
        ];
    }
}
