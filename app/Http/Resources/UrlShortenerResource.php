<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrlShortenerResource extends JsonResource
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
            'url_original' => $this->url_original,
            'url_corta' => $this->url_corta,
            'slug' => $this->slug,
            'clicks' => $this->clicks,
            'descripcion' => $this->descripcion,
            'expira_en' => $this->expira_en?->toISOString(),
            'is_expired' => $this->isExpired(),
            'is_available' => $this->isAvailable(),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre,
                ];
            }),
            'usuario' => $this->whenLoaded('usuario', function () {
                return [
                    'id' => $this->usuario->id,
                    'nombre' => $this->usuario->nombre,
                    'email' => $this->usuario->email,
                ];
            }),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
