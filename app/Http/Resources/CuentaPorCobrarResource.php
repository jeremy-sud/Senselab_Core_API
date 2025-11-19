<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Cuenta por Cobrar
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorCobrarResource extends JsonResource
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
            'cliente_id' => $this->cliente_id,
            'venta_id' => $this->venta_id,
            'documento_referencia' => $this->documento_referencia,
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'dias_vencimiento' => $this->fecha_vencimiento ? now()->diffInDays($this->fecha_vencimiento, false) : null,
            'esta_vencida' => $this->fecha_vencimiento && now()->isAfter($this->fecha_vencimiento),
            'moneda' => $this->moneda,
            'monto_original' => (float) $this->monto_original,
            'monto_pagado' => (float) $this->monto_pagado,
            'saldo_pendiente' => (float) $this->saldo_pendiente,
            'porcentaje_pagado' => $this->monto_original > 0 ? round(($this->monto_pagado / $this->monto_original) * 100, 2) : 0,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'cliente' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'identificacion' => $this->cliente->identificacion
                ];
            }),
            'venta' => $this->whenLoaded('venta', function () {
                return [
                    'id' => $this->venta->id,
                    'numero_factura' => $this->venta->numero_factura,
                    'total' => (float) $this->venta->total
                ];
            }),
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre
                ];
            }),
        ];
    }
}
