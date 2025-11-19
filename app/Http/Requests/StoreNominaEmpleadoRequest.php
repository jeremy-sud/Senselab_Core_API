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
            'horas_extras' => 'nullable|numeric|min:0',
            'monto_horas_extras' => 'nullable|numeric|min:0',
            'bonificaciones' => 'nullable|numeric|min:0',
            'deducciones_ccss' => 'nullable|numeric|min:0',
            'deducciones_impuesto_renta' => 'nullable|numeric|min:0',
            'otras_deducciones' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ];
    }
}
