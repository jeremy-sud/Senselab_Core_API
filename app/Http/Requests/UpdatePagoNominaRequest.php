<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Pago de Nómina
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdatePagoNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'empleado_id' => ['sometimes', 'integer', 'exists:empleados,id'],
            'periodo_nomina_id' => ['sometimes', 'integer', 'exists:periodos_nomina,id'],
            'fecha_pago' => ['sometimes', 'date'],
            'monto_bruto' => ['sometimes', 'numeric', 'min:0'],
            'total_deducciones' => ['sometimes', 'numeric', 'min:0'],
            'monto_neto_pagado' => ['sometimes', 'numeric', 'min:0'],
            'metodo_pago_id' => ['nullable', 'integer', 'exists:formas_pago,id'],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['pendiente', 'pagado', 'cancelado'])],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que monto_neto = monto_bruto - total_deducciones si se envían los 3
            if ($this->has('monto_bruto') && $this->has('total_deducciones') && $this->has('monto_neto_pagado')) {
                $netoCalculado = $this->monto_bruto - $this->total_deducciones;
                $netoEnviado = $this->monto_neto_pagado;
                
                if (abs($netoCalculado - $netoEnviado) > 0.01) {
                    $validator->errors()->add('monto_neto_pagado', 'El monto neto debe ser igual al monto bruto menos las deducciones');
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empleado_id.exists' => 'El empleado seleccionado no existe',
            'periodo_nomina_id.exists' => 'El período de nómina seleccionado no existe',
            'monto_bruto.min' => 'El monto bruto debe ser mayor o igual a 0',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no existe'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'empleado_id' => 'empleado',
            'periodo_nomina_id' => 'período de nómina',
            'fecha_pago' => 'fecha de pago',
            'monto_bruto' => 'monto bruto',
            'total_deducciones' => 'total de deducciones',
            'monto_neto_pagado' => 'monto neto',
            'metodo_pago_id' => 'método de pago',
            'referencia_pago' => 'referencia de pago',
            'estado' => 'estado',
            'observaciones' => 'observaciones'
        ];
    }
}
