<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaInventarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'almacen' => $this->whenLoaded('almacen', function () {
                return [
                    'id' => $this->almacen->id,
                    'nombre' => $this->almacen->nombre,
                    'sucursal' => $this->almacen->sucursal ? [
                        'id' => $this->almacen->sucursal->id,
                        'nombre' => $this->almacen->sucursal->nombre
                    ] : null
                ];
            }),
            'almacen_id' => $this->almacen_id,
            'fecha_entrada' => $this->fecha_entrada,
            'tipo_entrada' => $this->tipo_entrada,
            'orden_compra' => $this->whenLoaded('ordenCompra', function () {
                return $this->ordenCompra ? [
                    'id' => $this->ordenCompra->id,
                    'numero_orden' => $this->ordenCompra->numero_orden
                ] : null;
            }),
            'orden_compra_id' => $this->orden_compra_id,
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return $this->proveedor ? [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre
                ] : null;
            }),
            'proveedor_id' => $this->proveedor_id,
            'documento_referencia' => $this->documento_referencia,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado,
            'monto_total' => (float) $this->monto_total,
            'monto_total_formateado' => number_format($this->monto_total, 2, '.', ','),
            'detalles' => $this->whenLoaded('detalles', function () {
                return $this->detalles->map(function ($detalle) {
                    return [
                        'id' => $detalle->id,
                        'producto' => [
                            'id' => $detalle->producto->id,
                            'nombre' => $detalle->producto->nombre,
                            'codigo_barras' => $detalle->producto->codigo_barras,
                            'sku' => $detalle->producto->sku
                        ],
                        'cantidad' => (float) $detalle->cantidad,
                        'precio_unitario' => (float) $detalle->precio_unitario,
                        'subtotal' => (float) $detalle->subtotal,
                        'lote' => $detalle->lote,
                        'fecha_vencimiento' => $detalle->fecha_vencimiento
                    ];
                });
            }),
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en
        ];
    }
}
