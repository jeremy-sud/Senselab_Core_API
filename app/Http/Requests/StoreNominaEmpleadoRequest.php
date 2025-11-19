<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNominaEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_nomina_id' => 'required|integer|exists:periodos_nomina,id',
            'empleado_id' => 'required|integer|exists:empleados,id',
            'salario_bruto' => 'required|numeric|min:0|max:99999999.99',
            'horas_extras' => 'nullable|numeric|min:0|max:999.99',
            'monto_horas_extras' => 'nullable|numeric|min:0|max:99999999.99',
            'bonificaciones' => 'nullable|numeric|min:0|max:99999999.99',
            'deducciones_ccss' => 'nullable|numeric|min:0|max:99999999.99',
            'deducciones_impuesto_renta' => 'nullable|numeric|min:0|max:99999999.99',
            'otras_deducciones' => 'nullable|numeric|min:0|max:99999999.99',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'periodo_nomina_id.required' => 'El periodo de nómina es requerido',
            'periodo_nomina_id.exists' => 'El periodo de nómina no existe',
            'empleado_id.required' => 'El empleado es requerido',
            'empleado_id.exists' => 'El empleado no existe',
            'salario_bruto.required' => 'El salario bruto es requerido',
            'salario_bruto.min' => 'El salario debe ser mayor a 0',
        ];
    }
}
