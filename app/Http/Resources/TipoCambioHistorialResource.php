<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TipoCambioHistorialResource extends JsonResource
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
            'fecha' => $this->fecha,
            'moneda_origen' => $this->moneda_origen,
            'moneda_destino' => $this->moneda_destino,
            'par_monedas' => $this->moneda_origen . '/' . $this->moneda_destino,
            'tasa_compra' => (float) $this->tasa_compra,
            'tasa_venta' => (float) $this->tasa_venta,
            'diferencial' => round($this->tasa_venta - $this->tasa_compra, 5),
            'fuente' => $this->fuente,
            'creado_en' => $this->creado_en,
            
            // Cálculo de conversión inversa
            'conversion_inversa' => [
                'par_monedas' => $this->moneda_destino . '/' . $this->moneda_origen,
                'tasa_compra' => $this->tasa_compra > 0 ? round(1 / $this->tasa_compra, 5) : 0,
                'tasa_venta' => $this->tasa_venta > 0 ? round(1 / $this->tasa_venta, 5) : 0,
            ],
        ];
    }
}
