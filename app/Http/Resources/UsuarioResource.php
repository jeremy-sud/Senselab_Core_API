<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellidos' => $this->apellidos,
            'nombre_completo' => trim("{$this->nombre} {$this->apellidos}"),
            'cargo' => $this->whenLoaded('cargo', function () {
                return $this->cargo ? [
                    'id' => $this->cargo->id,
                    'nombre' => $this->cargo->nombre
                ] : null;
            }),
            'cargo_id' => $this->cargo_id,
            'email' => $this->email,
            'empresa' => $this->whenLoaded('empresa', function () {
                return $this->empresa ? [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre
                ] : null;
            }),
            'empresa_id' => $this->empresa_id,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($rol) {
                    return [
                        'id' => $rol->id,
                        'nombre' => $rol->nombre,
                        'permisos' => $rol->relationLoaded('permisos')
                            ? $rol->permisos->map(function ($permiso) {
                                return [
                                    'id' => $permiso->id,
                                    'slug' => $permiso->slug,
                                    'nombre' => $permiso->nombre,
                                    'modulo' => $permiso->modulo
                                ];
                            })
                            : []
                    ];
                });
            }),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
