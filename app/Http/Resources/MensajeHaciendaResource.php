<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MensajeHaciendaResource extends JsonResource
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
            'comprobante_id' => $this->comprobante_id,
            'clave_numerica' => $this->clave_numerica,
            'tipo_mensaje' => $this->tipo_mensaje,
            'mensaje' => $this->mensaje,
            'detalle_mensaje' => $this->detalle_mensaje,
            'fecha_emision' => $this->fecha_emision?->toISOString(),
            'estado' => $this->estado,
            'fecha_procesamiento' => $this->fecha_procesamiento?->toISOString(),
            'numero_consecutivo' => $this->numero_consecutivo,
            'codigo_actividad' => $this->codigo_actividad,
            'condicion_impuesto' => $this->condicion_impuesto,
            'monto_total_impuesto' => (float) $this->monto_total_impuesto,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
            'comprobante' => $this->whenLoaded('comprobante', fn() => [
                'id' => $this->comprobante->id,
                'numero_consecutivo' => $this->comprobante->numero_consecutivo,
            ]),
        ];
    }
}
