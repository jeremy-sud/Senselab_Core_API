<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class PlanillaCcssCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $periodo_nomina_id,
        public readonly string $periodo,
        public readonly string $fecha_generacion,
        public readonly ?string $fecha_presentacion = null,
        public readonly ?string $numero_planilla = null,
        public readonly int $total_empleados = 0,
        public readonly float $total_salarios = 0,
        public readonly float $total_cuota_obrera = 0,
        public readonly float $total_cuota_patronal = 0,
        public readonly float $total_a_pagar = 0,
        public readonly ?string $archivo_xml = null,
        public readonly ?string $archivo_pdf = null,
        public readonly string $estado = 'borrador',
        public readonly ?string $notas = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            periodo_nomina_id: (int) $request->input('periodo_nomina_id'),
            periodo: $request->string('periodo')->trim()->toString(),
            fecha_generacion: $request->string('fecha_generacion')->trim()->toString(),
            fecha_presentacion: $request->filled('fecha_presentacion') ? $request->string('fecha_presentacion')->trim()->toString() : null,
            numero_planilla: $request->filled('numero_planilla') ? $request->string('numero_planilla')->trim()->toString() : null,
            total_empleados: (int) $request->input('total_empleados', 0),
            total_salarios: (float) $request->input('total_salarios', 0),
            total_cuota_obrera: (float) $request->input('total_cuota_obrera', 0),
            total_cuota_patronal: (float) $request->input('total_cuota_patronal', 0),
            total_a_pagar: (float) $request->input('total_a_pagar', 0),
            archivo_xml: $request->filled('archivo_xml') ? $request->string('archivo_xml')->trim()->toString() : null,
            archivo_pdf: $request->filled('archivo_pdf') ? $request->string('archivo_pdf')->trim()->toString() : null,
            estado: $request->string('estado', 'borrador')->trim()->toString(),
            notas: $request->filled('notas') ? $request->string('notas')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'periodo' => $this->periodo,
            'fecha_generacion' => $this->fecha_generacion,
            'fecha_presentacion' => $this->fecha_presentacion,
            'numero_planilla' => $this->numero_planilla,
            'total_empleados' => $this->total_empleados,
            'total_salarios' => $this->total_salarios,
            'total_cuota_obrera' => $this->total_cuota_obrera,
            'total_cuota_patronal' => $this->total_cuota_patronal,
            'total_a_pagar' => $this->total_a_pagar,
            'archivo_xml' => $this->archivo_xml,
            'archivo_pdf' => $this->archivo_pdf,
            'estado' => $this->estado,
            'notas' => $this->notas,
        ];
    }
}
