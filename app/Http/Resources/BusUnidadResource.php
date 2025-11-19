<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Bus/Unidad de Transporte
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class BusUnidadResource extends JsonResource
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
            'placa' => $this->placa,
            'modelo_id' => $this->modelo_id,
            'modelo' => $this->whenLoaded('modelo', function () {
                return [
                    'id' => $this->modelo->id,
                    'nombre' => $this->modelo->nombre
                ];
            }),
            'capacidad_asientos' => $this->capacidad_asientos,
            'identificador_interno' => $this->identificador_interno,
            'activo' => (bool) $this->activo,
            'total_viajes' => $this->whenCounted('horariosRuta'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
