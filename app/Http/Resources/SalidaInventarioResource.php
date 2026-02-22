<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalidaInventarioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
            'fecha_salida' => $this->fecha_salida,
            'tipo_salida' => $this->tipo_salida,
            'venta' => $this->whenLoaded('venta', function () {
                return $this->venta ? [
                    'id' => $this->venta->id,
                    'numero_factura' => $this->venta->numero_factura
                ] : null;
            }),
            'venta_id' => $this->venta_id,
            'cliente' => $this->whenLoaded('cliente', function () {
                return $this->cliente ? [
                    'id' => $this->cliente->id,
                    'nombre' => $this->cliente->nombre,
                    'apellido' => $this->cliente->apellido
                ] : null;
            }),
            'cliente_id' => $this->cliente_id,
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return $this->proveedor ? [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre
                ] : null;
            }),
            'proveedor_id' => $this->proveedor_id,
            'documento_referencia' => $this->documento_referencia,
            'estado' => $this->estado,
            'monto_total' => (float) $this->monto_total,
            'monto_total_formateado' => number_format($this->monto_total, 2, '.', ','),
            'observaciones' => $this->observaciones,
            'descripcion' => $this->descripcion,
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
                        'costo_unitario' => (float) $detalle->costo_unitario,
                        'subtotal' => (float) $detalle->subtotal,
                        'lote' => $detalle->lote
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
