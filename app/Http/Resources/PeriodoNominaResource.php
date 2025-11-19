<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Período de Nómina
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PeriodoNominaResource extends JsonResource
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
            'nombre_periodo' => $this->nombre_periodo,
            'fecha_inicio' => $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin,
            'fecha_pago_estimada' => $this->fecha_pago_estimada,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'totales' => $this->when($this->relationLoaded('pagosNomina'), function () {
                $totales = $this->pagosNomina->reduce(function ($carry, $pago) {
                    $carry['total_empleados']++;
                    $carry['total_bruto'] += $pago->monto_bruto;
                    $carry['total_deducciones'] += $pago->total_deducciones;
                    $carry['total_neto'] += $pago->monto_neto_pagado;
                    return $carry;
                }, [
                    'total_empleados' => 0,
                    'total_bruto' => 0,
                    'total_deducciones' => 0,
                    'total_neto' => 0
                ]);

                return [
                    'total_empleados' => $totales['total_empleados'],
                    'total_bruto' => number_format($totales['total_bruto'], 2),
                    'total_deducciones' => number_format($totales['total_deducciones'], 2),
                    'total_neto' => number_format($totales['total_neto'], 2)
                ];
            }),
            'pagos' => PagoNominaResource::collection($this->whenLoaded('pagosNomina')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
