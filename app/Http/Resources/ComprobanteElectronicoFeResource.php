<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComprobanteElectronicoFeResource extends JsonResource
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
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
            'sucursal_id' => $this->sucursal_id,
            'sucursal' => new SucursalResource($this->whenLoaded('sucursal')),
            'tipo_comprobante_id' => $this->tipo_comprobante_id,
            'tipo_comprobante' => new TipoComprobanteFeResource($this->whenLoaded('tipoComprobante')),
            'consecutivo_fe_id' => $this->consecutivo_fe_id,
            'consecutivo' => new ConsecutivoFEResource($this->whenLoaded('consecutivo')),
            
            // Información del comprobante
            'numero_consecutivo' => $this->numero_consecutivo,
            'clave' => $this->clave,
            'fecha_emision' => $this->fecha_emision,
            'condicion_venta' => $this->condicion_venta,
            'plazo_credito' => $this->plazo_credito,
            'medio_pago' => $this->medio_pago,
            
            // Receptor
            'receptor_tipo_identificacion' => $this->receptor_tipo_identificacion,
            'receptor_identificacion' => $this->receptor_identificacion,
            'receptor_nombre' => $this->receptor_nombre,
            'receptor_nombre_comercial' => $this->receptor_nombre_comercial,
            'receptor_correo' => $this->receptor_correo,
            'receptor_telefono' => $this->receptor_telefono,
            
            // Montos
            'moneda' => $this->moneda,
            'tipo_cambio' => $this->tipo_cambio,
            'total_servicios_gravados' => $this->total_servicios_gravados,
            'total_servicios_exentos' => $this->total_servicios_exentos,
            'total_servicios_exonerados' => $this->total_servicios_exonerados,
            'total_mercaderias_gravadas' => $this->total_mercaderias_gravadas,
            'total_mercaderias_exentas' => $this->total_mercaderias_exentas,
            'total_mercaderias_exoneradas' => $this->total_mercaderias_exoneradas,
            'total_gravado' => $this->total_gravado,
            'total_exento' => $this->total_exento,
            'total_exonerado' => $this->total_exonerado,
            'total_venta' => $this->total_venta,
            'total_descuentos' => $this->total_descuentos,
            'total_venta_neta' => $this->total_venta_neta,
            'total_impuesto' => $this->total_impuesto,
            'total_comprobante' => $this->total_comprobante,
            
            // Otros
            'otros_cargos' => $this->otros_cargos,
            'otros_cargos_detalle' => $this->otros_cargos_detalle,
            
            // Estado y Hacienda
            'estado' => $this->estado,
            'estado_hacienda' => $this->estado_hacienda,
            'mensaje_hacienda' => $this->mensaje_hacienda,
            'fecha_envio_hacienda' => $this->fecha_envio_hacienda,
            'fecha_respuesta_hacienda' => $this->fecha_respuesta_hacienda,
            
            // XML
            'xml' => $this->when($request->user()?->hasPermissionTo('ver-facturacion_electronica'), $this->xml),
            'xml_respuesta' => $this->when($request->user()?->hasPermissionTo('ver-facturacion_electronica'), $this->xml_respuesta),
            
            // Relaciones
            'lineas_detalle' => FeLineaDetalleResource::collection($this->whenLoaded('lineasDetalle')),
            'mensajes_hacienda' => MensajeHaciendaResource::collection($this->whenLoaded('mensajesHacienda')),
            
            // Información de referencia (para notas de crédito/débito)
            'comprobante_referencia_tipo' => $this->comprobante_referencia_tipo,
            'comprobante_referencia_numero' => $this->comprobante_referencia_numero,
            'comprobante_referencia_fecha' => $this->comprobante_referencia_fecha,
            'comprobante_referencia_codigo' => $this->comprobante_referencia_codigo,
            'comprobante_referencia_razon' => $this->comprobante_referencia_razon,
            
            // Observaciones
            'observaciones' => $this->observaciones,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
