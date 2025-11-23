<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoComprobanteFeResource extends JsonResource
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
            'codigo_dgt' => $this->codigo_dgt,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'requiere_referencia' => (bool) $this->requiere_referencia,
            'permite_exportacion' => (bool) $this->permite_exportacion,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
