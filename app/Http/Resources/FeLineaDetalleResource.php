<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeLineaDetalleResource extends JsonResource
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
            'comprobante_electronico_id' => $this->comprobante_electronico_id,
            'comprobante' => new ComprobanteElectronicoFeResource($this->whenLoaded('comprobante')),
            
            // Número de línea
            'numero_linea' => $this->numero_linea,
            
            // Código del artículo/servicio
            'codigo_tipo' => $this->codigo_tipo,
            'codigo' => $this->codigo,
            
            // Descripción
            'detalle' => $this->detalle,
            'detalle_adicional' => $this->detalle_adicional,
            
            // Cantidades y unidades
            'cantidad' => $this->cantidad,
            'unidad_medida' => $this->unidad_medida,
            'unidad_medida_comercial' => $this->unidad_medida_comercial,
            
            // Precios
            'precio_unitario' => $this->precio_unitario,
            'monto_total' => $this->monto_total,
            
            // Descuentos
            'monto_descuento' => $this->monto_descuento,
            'naturaleza_descuento' => $this->naturaleza_descuento,
            
            // Subtotales
            'sub_total' => $this->sub_total,
            
            // Impuestos
            'impuesto_neto' => $this->impuesto_neto,
            'monto_total_linea' => $this->monto_total_linea,
            
            // Información de impuestos (JSON)
            'impuestos' => $this->impuestos,
            
            // Exoneraciones (JSON)
            'exoneraciones' => $this->exoneraciones,
            
            // CAByS (Catálogo de Bienes y Servicios)
            'cabys_codigo' => $this->cabys_codigo,
            'cabys' => new CabyResource($this->whenLoaded('cabys')),
            
            // Producto (si está vinculado)
            'producto_id' => $this->producto_id,
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            
            // Otros cargos
            'otros_cargos' => $this->otros_cargos,
            
            // Observaciones
            'observaciones' => $this->observaciones,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
