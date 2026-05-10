<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Ruta de Transporte
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class RutaResource extends JsonResource
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
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre
                ];
            }),
            'nombre' => $this->nombre,
            'origen' => $this->origen,
            'destino' => $this->destino,
            'distancia_km' => $this->distancia_km ? number_format($this->distancia_km, 2) : null,
            'duracion_estimada' => $this->duracion_estimada,
            'duracion_horas' => $this->duracion_estimada ? round($this->duracion_estimada / 60, 2) : null,
            'tarifa_base' => number_format($this->tarifa_base, 2),
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'total_horarios' => $this->whenCounted('horariosRuta'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
