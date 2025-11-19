<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'permisos' => $this->whenLoaded('permisos', function () {
                return $this->permisos->map(function ($permiso) {
                    return [
                        'id' => $permiso->id,
                        'nombre' => $permiso->nombre,
                        'codigo_unico' => $permiso->codigo_unico,
                        'modulo' => $permiso->modulo
                    ];
                });
            }),
            'usuarios_count' => $this->whenCounted('usuarios'),
            'permisos_count' => $this->whenCounted('permisos'),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
