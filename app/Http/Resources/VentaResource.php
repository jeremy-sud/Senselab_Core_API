<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
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
            'sucursal_id' => $this->sucursal_id,
            'numero_venta' => $this->numero_venta,
            'fecha_venta' => $this->fecha_venta?->toISOString(),
            'cliente_id' => $this->cliente_id,
            'cliente' => $this->whenLoaded('cliente', function () {
                return [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'identificacion' => $this->cliente->identificacion,
                    'email' => $this->cliente->email,
                ];
            }),
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->whenLoaded('usuario', function () {
                return [
                    'id' => $this->usuario->id,
                    'nombre' => $this->usuario->nombre,
                    'email' => $this->usuario->email,
                ];
            }),
            'tipo_venta' => $this->tipo_venta,
            'forma_pago_id' => $this->forma_pago_id,
            'forma_pago' => $this->whenLoaded('formaPago', function () {
                return [
                    'id' => $this->formaPago->id,
                    'nombre' => $this->formaPago->nombre,
                ];
            }),
            'subtotal' => (float) $this->subtotal,
            'descuento' => (float) $this->descuento,
            'impuestos' => (float) $this->impuestos,
            'total' => (float) $this->total,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            
            // Detalles de la venta
            'detalles' => $this->whenLoaded('detalles', function () {
                return $this->detalles->map(function ($detalle) {
                    return [
                        'id' => $detalle->id,
                        'producto_id' => $detalle->producto_id,
                        'producto_nombre' => $detalle->producto->nombre ?? null,
                        'producto_codigo' => $detalle->producto->codigo ?? null,
                        'cantidad' => (float) $detalle->cantidad,
                        'precio_unitario' => (float) $detalle->precio_unitario,
                        'descuento' => (float) $detalle->descuento,
                        'impuesto' => (float) $detalle->impuesto,
                        'subtotal' => (float) $detalle->subtotal,
                        'total' => (float) $detalle->total,
                    ];
                });
            }),
            
            // Factura electrónica
            'factura_electronica' => $this->whenLoaded('facturaElectronica', function () {
                return [
                    'id' => $this->facturaElectronica->id,
                    'clave_numerica' => $this->facturaElectronica->clave_numerica,
                    'estado' => $this->facturaElectronica->estado,
                ];
            }),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
