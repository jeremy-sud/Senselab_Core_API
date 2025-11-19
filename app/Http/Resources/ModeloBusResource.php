<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Modelo de Bus
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ModeloBusResource extends JsonResource
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
            'nombre' => $this->nombre,
            'buses_unidades_count' => $this->whenCounted('busesUnidades')
        ];
    }
}
