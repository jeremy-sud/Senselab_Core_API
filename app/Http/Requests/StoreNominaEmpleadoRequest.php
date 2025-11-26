<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNominaEmpleadoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'periodo_nomina_id' => 'required|integer|exists:periodos_nomina,id',
            'empleado_id' => 'required|integer|exists:empleados,id',
            'salario_bruto' => 'required|numeric|min:0',
            'horas_extras' => 'nullable|numeric|min:0|max:200',
            'monto_horas_extras' => 'nullable|numeric|min:0',
            'bonificaciones' => 'nullable|numeric|min:0',
            'deducciones_ccss' => 'nullable|numeric|min:0',
            'deducciones_impuesto_renta' => 'nullable|numeric|min:0',
            'otras_deducciones' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'periodo_nomina_id.required' => 'El período de nómina es obligatorio',
            'periodo_nomina_id.exists' => 'El período de nómina seleccionado no existe',
            'empleado_id.required' => 'El empleado es obligatorio',
            'empleado_id.exists' => 'El empleado seleccionado no existe',
            'salario_bruto.required' => 'El salario bruto es obligatorio',
            'salario_bruto.min' => 'El salario bruto debe ser mayor o igual a cero',
            'horas_extras.min' => 'Las horas extras deben ser mayor o igual a cero',
            'horas_extras.max' => 'Las horas extras no pueden exceder 200 horas por período',
            'deducciones_ccss.min' => 'Las deducciones de CCSS deben ser mayor o igual a cero',
            'deducciones_impuesto_renta.min' => 'Las deducciones de impuesto de renta deben ser mayor o igual a cero',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres',
        ];
    }

    public function attributes(): array
    {
        return [
            'periodo_nomina_id' => 'período de nómina',
            'empleado_id' => 'empleado',
            'salario_bruto' => 'salario bruto',
            'horas_extras' => 'horas extras',
            'monto_horas_extras' => 'monto de horas extras',
            'deducciones_ccss' => 'deducciones CCSS',
            'deducciones_impuesto_renta' => 'deducciones de impuesto de renta',
            'otras_deducciones' => 'otras deducciones',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el salario neto sea positivo
            $salarioBruto = (float)$this->salario_bruto;
            $bonificaciones = (float)($this->bonificaciones ?? 0);
            $montoHorasExtras = (float)($this->monto_horas_extras ?? 0);
            $deduccionesCCSS = (float)($this->deducciones_ccss ?? 0);
            $deduccionesRenta = (float)($this->deducciones_impuesto_renta ?? 0);
            $otrasDeducciones = (float)($this->otras_deducciones ?? 0);

            $salarioNeto = $salarioBruto + $bonificaciones + $montoHorasExtras - $deduccionesCCSS - $deduccionesRenta - $otrasDeducciones;

            if ($salarioNeto < 0) {
                $validator->errors()->add(
                    'deducciones_ccss',
                    'El total de deducciones excede el salario bruto. Salario neto resultante: ₡' . number_format($salarioNeto, 2)
                );
            }

            // Validar que si hay horas extras, debe haber monto
            if ($this->horas_extras > 0 && (!$this->monto_horas_extras || $this->monto_horas_extras <= 0)) {
                $validator->errors()->add(
                    'monto_horas_extras',
                    'Debe especificar el monto de las horas extras cuando se registran horas extras'
                );
            }
        });
    }
}
