<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriaActividadResource extends JsonResource
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
            'empresa_id' => $this->empresa_id,
            'accion' => $this->accion,
            'tabla' => $this->tabla,
            'registro_id' => $this->registro_id,
            'valores_anteriores' => $this->valores_anteriores,
            'valores_nuevos' => $this->valores_nuevos,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'cambios' => $this->when(isset($this->cambios), $this->cambios),
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
        ];
    }
}
