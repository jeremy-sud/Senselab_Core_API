<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZonaGeograficaResource extends JsonResource
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
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'zona_padre_id' => $this->zona_padre_id,
            'provincias_incluidas' => $this->provincias_incluidas,
            'vendedor_asignado_id' => $this->vendedor_asignado_id,
            'activa' => (bool) $this->activa,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
            'zona_padre' => $this->whenLoaded('zonaPadre', fn() => [
                'id' => $this->zonaPadre->id,
                'nombre' => $this->zonaPadre->nombre,
                'tipo' => $this->zonaPadre->tipo,
            ]),
            'vendedor_asignado' => $this->whenLoaded('vendedorAsignado', fn() => [
                'id' => $this->vendedorAsignado->id,
                'nombre_completo' => $this->vendedorAsignado->nombre_completo,
            ]),
        ];
    }
}
