<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SucursalResource extends JsonResource
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
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'provincia' => $this->provincia,
            'canton' => $this->canton,
            'distrito' => $this->distrito,
            'es_principal' => (bool) $this->es_principal,
            'activo' => (bool) $this->activo,
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre_comercial' => $this->empresa->nombre_comercial,
                    'razon_social' => $this->empresa->razon_social,
                ];
            }),
            
            // Estadísticas
            'almacenes_count' => $this->whenCounted('almacenes'),
            'cajas_count' => $this->whenCounted('cajas'),
            'ventas_count' => $this->whenCounted('ventas'),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
