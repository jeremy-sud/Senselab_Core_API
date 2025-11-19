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
            'nombre' => $this->nombre,
            'monto_inicial' => (float) $this->monto_inicial,
            'saldo_actual' => (float) $this->saldo_actual,
            'responsable_id' => $this->responsable_id,
            'fecha_apertura' => $this->fecha_apertura,
            'fecha_cierre' => $this->fecha_cierre,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'responsable' => $this->whenLoaded('responsable', function () {
                return [
                    'id' => $this->responsable->id,
                    'nombre_completo' => $this->responsable->nombre_completo,
                ];
            }),
            
            'empresa' => $this->whenLoaded('empresa'),
        ];
    }
}
