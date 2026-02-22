<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermisoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'modulo' => $this->modulo,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($rol) {
                    return [
                        'id' => $rol->id,
                        'nombre' => $rol->nombre
                    ];
                });
            }),
            'roles_count' => $this->whenCounted('roles'),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
