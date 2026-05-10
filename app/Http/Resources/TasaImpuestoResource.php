<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Tasa de Impuesto
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class TasaImpuestoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hoy = Carbon::now()->toDateString();
        $vigente = $this->fecha_inicio_vigencia <= $hoy && 
                   ($this->fecha_fin_vigencia === null || $this->fecha_fin_vigencia >= $hoy);

        return [
            'id' => $this->id,
            'tipo_impuesto_id' => $this->tipo_impuesto_id,
            'tipo_impuesto' => $this->whenLoaded('tipoImpuesto', function () {
                return [
                    'id' => $this->tipoImpuesto->id,
                    'nombre' => $this->tipoImpuesto->nombre,
                    'codigo' => $this->tipoImpuesto->codigo
                ];
            }),
            'tasa_porcentaje' => number_format($this->tasa_porcentaje, 2),
            'tasa_decimal' => number_format($this->tasa_porcentaje / 100, 4),
            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia,
            'fecha_fin_vigencia' => $this->fecha_fin_vigencia,
            'vigencia_actual' => $vigente,
            'dias_vigencia' => $this->when($this->fecha_fin_vigencia !== null, function () {
                return Carbon::parse($this->fecha_inicio_vigencia)
                    ->diffInDays(Carbon::parse($this->fecha_fin_vigencia));
            }),
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
