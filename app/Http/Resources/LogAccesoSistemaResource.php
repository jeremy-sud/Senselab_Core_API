<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogAccesoSistemaResource extends JsonResource
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
            'usuario_id' => $this->usuario_id,
            'email' => $this->email,
            'tipo_evento' => $this->tipo_evento,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metodo_autenticacion' => $this->metodo_autenticacion,
            'razon_fallo' => $this->razon_fallo,
            'sesion_id' => $this->sesion_id,
            'duracion_sesion' => $this->duracion_sesion,
            'pais' => $this->pais,
            'ciudad' => $this->ciudad,
            'fecha_acceso' => $this->created_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'usuario' => $this->whenLoaded('usuario', fn() => [
                'id' => $this->usuario->id,
                'name' => $this->usuario->name,
                'email' => $this->usuario->email,
            ]),
        ];
    }
}
