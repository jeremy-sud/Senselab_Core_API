<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Comprobante Electrónico Recibido
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class ComprobanteRecibidoElectronicoResource extends JsonResource
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
            'proveedor' => $this->whenLoaded('proveedor', function () {
                return [
                    'id' => $this->proveedor->id,
                    'nombre' => $this->proveedor->nombre,
                    'identificacion' => $this->proveedor->identificacion
                ];
            }),
            'clave_numerica' => $this->clave_numerica,
            'consecutivo_receptor' => $this->consecutivo_receptor,
            'tipo_documento_dgt' => $this->tipo_documento_dgt,
            'tipo_documento_nombre' => $this->obtenerNombreTipoDocumento(),
            'fecha_emision_comprobante' => $this->fecha_emision_comprobante,
            'fecha_recepcion_sistema' => $this->fecha_recepcion_sistema,
            'moneda' => $this->moneda,
            'total_impuesto' => $this->total_impuesto ? number_format($this->total_impuesto, 2) : null,
            'total_comprobante' => number_format($this->total_comprobante, 2),
            'estado_hacienda' => $this->estado_hacienda,
            'mensaje_hacienda' => $this->mensaje_hacienda,
            'fecha_respuesta_hacienda' => $this->fecha_respuesta_hacienda,
            'confirmado_usuario' => $this->confirmado_usuario,
            'confirmado_usuario_texto' => $this->obtenerTextoConfirmacion(),
            'fecha_confirmacion_usuario' => $this->fecha_confirmacion_usuario,
            'usuario_confirmacion' => $this->whenLoaded('usuarioConfirmacion', function () {
                return [
                    'id' => $this->usuarioConfirmacion->id,
                    'nombre' => $this->usuarioConfirmacion->nombre
                ];
            }),
            'entrada_inventario_id' => $this->entrada_inventario_id,
            'entrada_inventario' => $this->whenLoaded('entradaInventario', function () {
                return [
                    'id' => $this->entradaInventario->id,
                    'fecha_entrada' => $this->entradaInventario->fecha_entrada
                ];
            }),
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }

    private function obtenerNombreTipoDocumento(): string
    {
        $tipos = [
            '01' => 'Factura Electrónica',
            '02' => 'Nota de Débito',
            '03' => 'Nota de Crédito',
            '04' => 'Tiquete Electrónico',
            '08' => 'Factura de Compra',
            '09' => 'Factura de Exportación'
        ];

        return $tipos[$this->tipo_documento_dgt] ?? 'Desconocido';
    }

    private function obtenerTextoConfirmacion(): string
    {
        return match($this->confirmado_usuario) {
            0 => 'Pendiente',
            1 => 'Confirmado',
            2 => 'Rechazado',
            default => 'Desconocido'
        };
    }
}
