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
            'fecha_vencimiento' => $this->fecha_vencimiento?->toISOString(),
            'numero_formulario' => $this->numero_formulario,
            'total_ventas_gravadas' => (float) $this->total_ventas_gravadas,
            'total_ventas_exentas' => (float) $this->total_ventas_exentas,
            'total_compras_gravadas' => (float) $this->total_compras_gravadas,
            'total_iva_debito' => (float) $this->total_iva_debito,
            'total_iva_credito' => (float) $this->total_iva_credito,
            'iva_a_pagar' => (float) $this->iva_a_pagar,
            'iva_a_favor' => (float) $this->iva_a_favor,
            'monto_pagado' => (float) $this->monto_pagado,
            'fecha_pago' => $this->fecha_pago?->toISOString(),
            'estado' => $this->estado,
            'archivo_xml' => $this->archivo_xml,
            'archivo_pdf' => $this->archivo_pdf,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
        ];
    }
}
