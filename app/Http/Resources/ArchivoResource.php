<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArchivoResource extends JsonResource
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
            'usuario_id' => $this->usuario_id,
            'entidad_tipo' => $this->entidad_tipo,
            'entidad_id' => $this->entidad_id,
            'nombre_original' => $this->nombre_original,
            'nombre_almacenado' => $this->nombre_almacenado,
            'ruta' => $this->ruta,
            'tipo_mime' => $this->tipo_mime,
            'extension' => $this->extension,
            'tamano_bytes' => $this->tamano_bytes,
            'categoria' => $this->categoria,
            'hash_sha256' => $this->hash_sha256,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
        ];
    }
}
