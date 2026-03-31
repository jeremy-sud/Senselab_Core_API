<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'url' => $this->url,
            'eventos' => $this->eventos,
            'descripcion' => $this->descripcion,
            'timeout_segundos' => $this->timeout_segundos,
            'max_reintentos' => $this->max_reintentos,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
        ];
    }
}
