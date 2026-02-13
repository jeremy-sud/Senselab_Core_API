<?php

namespace App\DTOs\API\Nomina;

use Illuminate\Http\Request;

/**
 * NominaEmpleadoCreateDTO - DTO para crear nómina de empleado
 * 
 * Calcula salarios, deducciones y aportes según legislación costarricense.
 */
final class NominaEmpleadoCreateDTO
{
    private function __construct(
        public readonly int $empleado_id,
        public readonly \DateTime $fecha_inicio,
        public readonly \DateTime $fecha_fin,
        public readonly float $salario_bruto,
        public readonly float $dias_laborados,
        public readonly array $deducciones, // CAJA, SEGURO, IMPUESTO, ETC
        public readonly array $aportes, // PATRONAL
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empleado_id: (int) $request->input('empleado_id'),
            fecha_inicio: new \DateTime($request->input('fecha_inicio')),
            fecha_fin: new \DateTime($request->input('fecha_fin')),
            salario_bruto: (float) $request->input('salario_bruto'),
            dias_laborados: (float) $request->input('dias_laborados'),
            deducciones: $request->input('deducciones', []),
            aportes: $request->input('aportes', []),
            observaciones: $request->input('observaciones'),
        );
    }

    /**
     * Calcula total de deducciones
     */
    public function totalDeducciones(): float
    {
        return (float) array_sum(array_values($this->deducciones));
    }

    /**
     * Calcula total de aportes patronales
     */
    public function totalAportes(): float
    {
        return (float) array_sum(array_values($this->aportes));
    }

    /**
     * Calcula salario neto
     */
    public function salarioNeto(): float
    {
        return round($this->salario_bruto - $this->totalDeducciones(), 2);
    }

    /**
     * Calcula costo total para empresa
     */
    public function costoTotal(): float
    {
        return round($this->salario_bruto + $this->totalAportes(), 2);
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'empleado_id' => $this->empleado_id,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'salario_bruto' => $this->salario_bruto,
            'dias_laborados' => $this->dias_laborados,
            'total_deducciones' => $this->totalDeducciones(),
            'total_aportes' => $this->totalAportes(),
            'salario_neto' => $this->salarioNeto(),
            'costo_total' => $this->costoTotal(),
            'deducciones' => json_encode($this->deducciones),
            'aportes' => json_encode($this->aportes),
            'observaciones' => $this->observaciones,
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'empleado_id' => 'required|integer|exists:empleados,id',
            'fecha_inicio' => 'required|date|before:fecha_fin',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'salario_bruto' => 'required|numeric|min:0',
            'dias_laborados' => 'required|numeric|min:0|max:31',
            'deducciones' => 'nullable|array',
            'aportes' => 'nullable|array',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public static function messages(): array
    {
        return [
            'empleado_id.exists' => 'El empleado no existe',
            'fecha_inicio.before' => 'La fecha inicio debe ser anterior a fecha fin',
            'dias_laborados.max' => 'Los días no pueden exceder 31',
        ];
    }
}
