<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleVentaResource extends JsonResource
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
            'venta_id' => $this->venta_id,
            'producto_id' => $this->producto_id,
            'numero_linea' => $this->numero_linea,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'porcentaje_descuento' => $this->porcentaje_descuento,
            'monto_descuento' => $this->monto_descuento,
            'subtotal_linea' => $this->subtotal_linea,
            'subtotal_con_descuento' => $this->subtotal_con_descuento,
            'tipo_impuesto_id' => $this->tipo_impuesto_id,
            'tasa_impuesto' => $this->tasa_impuesto,
            'monto_impuesto' => $this->monto_impuesto,
            'total_linea' => $this->total_linea,
            'detalle_adicional' => $this->detalle_adicional,
            'activo' => $this->activo,
            'venta' => new VentaResource($this->whenLoaded('venta')),
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            'tipo_impuesto' => new TipoImpuestoResource($this->whenLoaded('tipoImpuesto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
