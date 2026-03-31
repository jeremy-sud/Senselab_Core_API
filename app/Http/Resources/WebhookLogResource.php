<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'webhook_id' => $this->webhook_id,
            'evento' => $this->evento,
            'estado' => $this->estado,
            'codigo_respuesta' => $this->codigo_respuesta,
            'latencia_ms' => $this->latencia_ms,
            'payload_size' => $this->payload_size,
            'error' => $this->error,
            'intento' => $this->intento,
            'proximo_reintento_en' => $this->proximo_reintento_en,
            'creado_en' => $this->creado_en,
        ];
    }
}
