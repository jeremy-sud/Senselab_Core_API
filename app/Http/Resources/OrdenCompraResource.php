<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenCompraResource extends JsonResource
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
            'numero_orden' => $this->numero_orden,
            'fecha_orden' => $this->fecha_orden?->toISOString(),
            'fecha_entrega_estimada' => $this->fecha_entrega_estimada?->toISOString(),
            'proveedor_id' => $this->proveedor_id,
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                    'identificacion' => $this->proveedor->identificacion,
                    'email' => $this->proveedor->email,
                ];
            }),
            'almacen_id' => $this->almacen_id,
            'almacen' => $this->whenLoaded('almacen', function () {
                return [
                    'id' => $this->almacen->id,
                    'nombre' => $this->almacen->nombre,
                    'codigo' => $this->almacen->codigo,
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
            'subtotal' => (float) $this->subtotal,
            'descuento' => (float) $this->descuento,
            'impuestos' => (float) $this->impuestos,
            'total' => (float) $this->total,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            
            // Detalles de la orden
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
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
            'aprobado_en' => $this->aprobado_en?->toISOString(),
            'recibido_en' => $this->recibido_en?->toISOString(),
        ];
    }
}
