<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolPermisoResource extends JsonResource
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
            'rol_id' => $this->rol_id ?? null,
            'permiso_id' => $this->permiso_id,
            'activo' => (bool) $this->activo,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'rol' => $this->whenLoaded('rol', function () {
                return [
                    'id' => $this->rol->id,
                    'nombre' => $this->rol->nombre,
                    'descripcion' => $this->rol->descripcion,
                ];
            }),
            
            'permiso' => $this->whenLoaded('permiso', function () {
                return [
                    'id' => $this->permiso->id,
                    'nombre' => $this->permiso->nombre,
                    'descripcion' => $this->permiso->descripcion,
                ];
            }),
        ];
    }
}
