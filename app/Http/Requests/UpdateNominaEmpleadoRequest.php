<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNominaEmpleadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periodo_nomina_id' => 'sometimes|integer|exists:periodos_nomina,id',
            'empleado_id' => 'sometimes|integer|exists:empleados,id',
            'salario_bruto' => 'sometimes|numeric|min:0|max:99999999.99',
            'horas_extras' => 'sometimes|numeric|min:0|max:999.99',
            'monto_horas_extras' => 'sometimes|numeric|min:0|max:99999999.99',
            'bonificaciones' => 'sometimes|numeric|min:0|max:99999999.99',
            'deducciones_ccss' => 'sometimes|numeric|min:0|max:99999999.99',
            'deducciones_impuesto_renta' => 'sometimes|numeric|min:0|max:99999999.99',
            'otras_deducciones' => 'sometimes|numeric|min:0|max:99999999.99',
            'observaciones' => 'nullable|string',
        ];
    }
}
