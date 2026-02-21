<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de pago de nómina
 *
 * Valida y transforma datos de entrada para la creación de pagos de nómina
 * Fecha de creación: 12 de febrero de 2026
 */
final class PagoNominaCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $empleado_id,
        public readonly int $periodo_nomina_id,
        public readonly float $salario_base,
        public readonly float $descuentos,
        public readonly float $bonificaciones,
        public readonly float $total_pago,
        public readonly \DateTime $fecha_pago,
        public readonly string $estado,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            empleado_id: $request->integer('empleado_id'),
            periodo_nomina_id: $request->integer('periodo_nomina_id'),
            salario_base: $request->float('salario_base'),
            descuentos: $request->float('descuentos'),
            bonificaciones: $request->float('bonificaciones'),
            total_pago: $request->float('total_pago'),
            fecha_pago: new \DateTime($request->string('fecha_pago')),
            estado: $request->string('estado'),
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'empleado_id' => $this->empleado_id,
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'salario_base' => $this->salario_base,
            'descuentos' => $this->descuentos,
            'bonificaciones' => $this->bonificaciones,
            'total_pago' => $this->total_pago,
            'fecha_pago' => $this->fecha_pago->format('Y-m-d'),
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ];
    }
}
