<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Cuenta por Pagar
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class CuentaPorPagarResource extends JsonResource
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
            'proveedor_id' => $this->proveedor_id,
            'orden_compra_id' => $this->orden_compra_id,
            'comprobante_recibido_id' => $this->comprobante_recibido_id,
            'documento_referencia_proveedor' => $this->documento_referencia_proveedor,
            'fecha_emision_documento' => $this->fecha_emision_documento,
            'fecha_recepcion_documento' => $this->fecha_recepcion_documento,
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
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                    'identificacion' => $this->proveedor->identificacion
                ];
            }),
            'orden_compra' => $this->whenLoaded('ordenCompra', function () {
                return [
                    'id' => $this->ordenCompra->id,
                    'numero_orden' => $this->ordenCompra->numero_orden,
                    'total' => (float) $this->ordenCompra->total
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
