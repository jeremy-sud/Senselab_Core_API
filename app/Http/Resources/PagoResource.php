<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Pago
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class PagoResource extends JsonResource
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
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre
                ];
            }),
            'orden_compra_id' => $this->orden_compra_id,
            'orden_compra' => $this->whenLoaded('ordenCompra', function () {
                return [
                    'id' => $this->ordenCompra->id,
                    'numero_orden' => $this->ordenCompra->numero_orden
                ];
            }),
            'cuenta_por_pagar_id' => $this->cuenta_por_pagar_id,
            'cuenta_por_pagar' => $this->whenLoaded('cuentaPorPagar', function () {
                return [
                    'id' => $this->cuentaPorPagar->id,
                    'numero_cuenta' => $this->cuentaPorPagar->numero_cuenta,
                    'monto_total' => number_format($this->cuentaPorPagar->monto_total, 2),
                    'monto_pagado' => number_format($this->cuentaPorPagar->monto_pagado, 2)
                ];
            }),
            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                    'numero_identificacion' => $this->proveedor->numero_identificacion
                ];
            }),
            'cliente_id' => $this->cliente_id,
            'cliente' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'numero_identificacion' => $this->cliente->numero_identificacion
                ];
            }),
            'cuenta_por_cobrar_id' => $this->cuenta_por_cobrar_id,
            'cuenta_por_cobrar' => $this->whenLoaded('cuentaPorCobrar', function () {
                return [
                    'id' => $this->cuentaPorCobrar->id,
                    'numero_cuenta' => $this->cuentaPorCobrar->numero_cuenta,
                    'monto_total' => number_format($this->cuentaPorCobrar->monto_total, 2),
                    'monto_pagado' => number_format($this->cuentaPorCobrar->monto_pagado, 2)
                ];
            }),
            'forma_pago_id' => $this->forma_pago_id,
            'forma_pago' => $this->whenLoaded('formaPago', function () {
                return [
                    'id' => $this->formaPago->id,
                    'nombre' => $this->formaPago->nombre
                ];
            }),
            'fecha_pago' => $this->fecha_pago,
            'monto' => number_format($this->monto, 2),
            'moneda' => $this->moneda,
            'descripcion' => $this->descripcion,
            'referencia' => $this->referencia,
            'estado' => $this->estado,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
