<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeclaracionTributariaResource extends JsonResource
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
            'tipo_declaracion' => $this->tipo_declaracion,
            'periodo_fiscal' => $this->periodo_fiscal,
            'fecha_inicio_periodo' => $this->fecha_inicio_periodo?->toISOString(),
            'fecha_fin_periodo' => $this->fecha_fin_periodo?->toISOString(),
            'fecha_presentacion' => $this->fecha_presentacion?->toISOString(),
            'monto_base_imponible' => (float) $this->monto_base_imponible,
            'monto_impuesto' => (float) $this->monto_impuesto,
            'monto_creditos' => (float) $this->monto_creditos,
            'monto_debitos' => (float) $this->monto_debitos,
            'monto_a_pagar' => (float) $this->monto_a_pagar,
            'monto_a_favor' => (float) $this->monto_a_favor,
            'numero_confirmacion' => $this->numero_confirmacion,
            'archivo_xml' => $this->archivo_xml,
            'archivo_pdf' => $this->archivo_pdf,
            'estado' => $this->estado,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
        ];
    }
}
