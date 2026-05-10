<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Pago de Nómina
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class PagoNominaResource extends JsonResource
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
            'empresa' => $this->whenLoaded('empresa', function () {
                return [
                    'id' => $this->empresa->id,
                    'nombre' => $this->empresa->nombre
                ];
            }),
            'empleado_id' => $this->empleado_id,
            'empleado' => $this->whenLoaded('empleado', function () {
                return [
                    'id' => $this->empleado->id,
                    'nombre_completo' => $this->empleado->nombre . ' ' . $this->empleado->apellido1 . ' ' . $this->empleado->apellido2,
                    'numero_identificacion' => $this->empleado->numero_identificacion,
                    'cargo' => $this->empleado->cargo?->nombre
                ];
            }),
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'periodo_nomina' => $this->whenLoaded('periodoNomina', function () {
                return [
                    'id' => $this->periodoNomina->id,
                    'nombre_periodo' => $this->periodoNomina->nombre_periodo,
                    'fecha_inicio' => $this->periodoNomina->fecha_inicio,
                    'fecha_fin' => $this->periodoNomina->fecha_fin,
                    'estado' => $this->periodoNomina->estado
                ];
            }),
            'fecha_pago' => $this->fecha_pago,
            'monto_bruto' => number_format($this->monto_bruto, 2),
            'total_deducciones' => number_format($this->total_deducciones, 2),
            'monto_neto_pagado' => number_format($this->monto_neto_pagado, 2),
            'porcentaje_deducciones' => $this->monto_bruto > 0 
                ? number_format(($this->total_deducciones / $this->monto_bruto) * 100, 2) 
                : '0.00',
            'metodo_pago_id' => $this->metodo_pago_id,
            'metodo_pago' => $this->whenLoaded('metodoPago', function () {
                return [
                    'id' => $this->metodoPago->id,
                    'nombre' => $this->metodoPago->nombre
                ];
            }),
            'referencia_pago' => $this->referencia_pago,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
