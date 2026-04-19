<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class NominaEmpleadoCreateDTO
{
    public function __construct(
        public readonly int $periodo_nomina_id,
        public readonly int $empleado_id,
        public readonly float $salario_bruto,
        public readonly float $horas_extras = 0,
        public readonly float $monto_horas_extras = 0,
        public readonly float $bonificaciones = 0,
        public readonly float $total_devengado = 0,
        public readonly float $deducciones_ccss = 0,
        public readonly float $deducciones_impuesto_renta = 0,
        public readonly float $otras_deducciones = 0,
        public readonly float $total_deducciones = 0,
        public readonly float $salario_neto = 0,
        public readonly ?string $observaciones = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            periodo_nomina_id: (int) $request->input('periodo_nomina_id'),
            empleado_id: (int) $request->input('empleado_id'),
            salario_bruto: (float) $request->input('salario_bruto'),
            horas_extras: (float) $request->input('horas_extras', 0),
            monto_horas_extras: (float) $request->input('monto_horas_extras', 0),
            bonificaciones: (float) $request->input('bonificaciones', 0),
            total_devengado: (float) $request->input('total_devengado', 0),
            deducciones_ccss: (float) $request->input('deducciones_ccss', 0),
            deducciones_impuesto_renta: (float) $request->input('deducciones_impuesto_renta', 0),
            otras_deducciones: (float) $request->input('otras_deducciones', 0),
            total_deducciones: (float) $request->input('total_deducciones', 0),
            salario_neto: (float) $request->input('salario_neto', 0),
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'empleado_id' => $this->empleado_id,
            'salario_bruto' => $this->salario_bruto,
            'horas_extras' => $this->horas_extras,
            'monto_horas_extras' => $this->monto_horas_extras,
            'bonificaciones' => $this->bonificaciones,
            'total_devengado' => $this->total_devengado,
            'deducciones_ccss' => $this->deducciones_ccss,
            'deducciones_impuesto_renta' => $this->deducciones_impuesto_renta,
            'otras_deducciones' => $this->otras_deducciones,
            'total_deducciones' => $this->total_deducciones,
            'salario_neto' => $this->salario_neto,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
        ];
    }
}
