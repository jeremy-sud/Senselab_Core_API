<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Tiquete de Transporte
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class TiqueteDetalleResource extends JsonResource
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
            'detalle_venta_id' => $this->detalle_venta_id,
            'detalle_venta' => $this->whenLoaded('detalleVenta', function () {
                return [
                    'id' => $this->detalleVenta->id,
                    'venta_id' => $this->detalleVenta->venta_id
                ];
            }),
            'horario_ruta_id' => $this->horario_ruta_id,
            'horario_ruta' => $this->whenLoaded('horarioRuta', function () {
                return [
                    'id' => $this->horarioRuta->id,
                    'fecha_salida' => $this->horarioRuta->fecha_salida,
                    'hora_salida' => $this->horarioRuta->hora_salida,
                    'estado' => $this->horarioRuta->estado,
                    'ruta' => $this->horarioRuta->ruta ? [
                        'nombre' => $this->horarioRuta->ruta->nombre,
                        'origen' => $this->horarioRuta->ruta->origen,
                        'destino' => $this->horarioRuta->ruta->destino
                    ] : null,
                    'bus' => $this->horarioRuta->bus ? [
                        'placa' => $this->horarioRuta->bus->placa,
                        'identificador_interno' => $this->horarioRuta->bus->identificador_interno
                    ] : null
                ];
            }),
            'asiento_numero' => $this->asiento_numero,
            'nombre_pasajero' => $this->nombre_pasajero,
            'identificacion_pasajero' => $this->identificacion_pasajero,
            'precio_final_tiquete' => number_format($this->precio_final_tiquete, 2),
            'estado' => $this->estado,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
