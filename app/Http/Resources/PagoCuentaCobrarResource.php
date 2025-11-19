<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoCuentaCobrarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cuenta_por_cobrar_id' => $this->cuenta_por_cobrar_id,
            'forma_pago_id' => $this->forma_pago_id,
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),
            'monto_pago' => (float) $this->monto_pago,
            'numero_referencia' => $this->numero_referencia,
            'moneda' => $this->moneda,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            'cuenta_por_cobrar' => $this->whenLoaded('cuentaPorCobrar'),
            'forma_pago' => $this->whenLoaded('formaPago'),
        ];
    }
}
