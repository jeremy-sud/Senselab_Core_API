<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Asiento Contable
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class AsientoContableResource extends JsonResource
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
            'fecha' => $this->fecha,
            'descripcion' => $this->descripcion,
            'total_debe' => number_format($this->total_debe, 2),
            'total_haber' => number_format($this->total_haber, 2),
            'estado' => $this->estado,
            'activo' => (bool) $this->activo,
            'detalles' => DetalleAsientoResource::collection($this->whenLoaded('detalles')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
