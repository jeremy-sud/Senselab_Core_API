<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Horario de Ruta (Viaje Programado)
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class HorarioRutaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tiquetesVendidos = $this->whenLoaded('tiquetesDetalle', function () {
            return $this->tiquetesDetalle->where('estado', '!=', 'Cancelado')->count();
        }, 0);

        return [
            'id' => $this->id,
            'ruta_id' => $this->ruta_id,
            'ruta' => $this->whenLoaded('ruta', function () {
                return [
                    'id' => $this->ruta->id,
                    'nombre' => $this->ruta->nombre,
                    'origen' => $this->ruta->origen,
                    'destino' => $this->ruta->destino,
                    'tarifa_base' => number_format($this->ruta->tarifa_base, 2)
                ];
            }),
            'bus_id' => $this->bus_id,
            'bus' => $this->whenLoaded('bus', function () {
                return [
                    'id' => $this->bus->id,
                    'placa' => $this->bus->placa,
                    'identificador_interno' => $this->bus->identificador_interno,
                    'capacidad_asientos' => $this->bus->capacidad_asientos,
                    'modelo' => $this->bus->modelo?->nombre
                ];
            }),
            'fecha_salida' => $this->fecha_salida,
            'hora_salida' => $this->hora_salida,
            'fecha_llegada_estimada' => $this->fecha_llegada_estimada,
            'hora_llegada_estimada' => $this->hora_llegada_estimada,
            'asientos_disponibles' => $this->asientos_disponibles,
            'asientos_ocupados' => is_int($tiquetesVendidos) ? $tiquetesVendidos : 0,
            'porcentaje_ocupacion' => $this->bus && $this->bus->capacidad_asientos > 0
                ? round((is_int($tiquetesVendidos) ? $tiquetesVendidos : 0) / $this->bus->capacidad_asientos * 100, 2)
                : 0,
            'estado' => $this->estado,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
