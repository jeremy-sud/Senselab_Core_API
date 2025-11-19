<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Pago de Nómina
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StorePagoNominaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'periodo_nomina_id' => ['required', 'integer', 'exists:periodos_nomina,id'],
            'fecha_pago' => ['required', 'date'],
            'monto_bruto' => ['required', 'numeric', 'min:0'],
            'total_deducciones' => ['required', 'numeric', 'min:0'],
            'monto_neto_pagado' => ['required', 'numeric', 'min:0'],
            'metodo_pago_id' => ['nullable', 'integer', 'exists:formas_pago,id'],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['pendiente', 'pagado', 'cancelado'])],
            'observaciones' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que monto_neto = monto_bruto - total_deducciones
            if ($this->has('monto_bruto') && $this->has('total_deducciones') && $this->has('monto_neto_pagado')) {
                $netoCalculado = $this->monto_bruto - $this->total_deducciones;
                $netoEnviado = $this->monto_neto_pagado;
                
                if (abs($netoCalculado - $netoEnviado) > 0.01) {
                    $validator->errors()->add('monto_neto_pagado', 'El monto neto debe ser igual al monto bruto menos las deducciones');
                }
            }

            // Validar que no exista duplicado para el mismo empleado y período
            if ($this->has('empleado_id') && $this->has('periodo_nomina_id')) {
                $empresaId = $this->user()->empresa_id;
                
                $duplicado = \App\Models\PagoNomina::where('empresa_id', $empresaId)
                    ->where('empleado_id', $this->empleado_id)
                    ->where('periodo_nomina_id', $this->periodo_nomina_id)
                    ->where('eliminado', 0)
                    ->exists();

                if ($duplicado) {
                    $validator->errors()->add('empleado_id', 'Ya existe un pago de nómina para este empleado en el período seleccionado');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'empleado_id.required' => 'El empleado es obligatorio',
            'empleado_id.exists' => 'El empleado seleccionado no existe',
            'periodo_nomina_id.required' => 'El período de nómina es obligatorio',
            'periodo_nomina_id.exists' => 'El período de nómina seleccionado no existe',
            'fecha_pago.required' => 'La fecha de pago es obligatoria',
            'monto_bruto.required' => 'El monto bruto es obligatorio',
            'monto_bruto.min' => 'El monto bruto debe ser mayor o igual a 0',
            'total_deducciones.required' => 'El total de deducciones es obligatorio',
            'monto_neto_pagado.required' => 'El monto neto es obligatorio',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no existe'
        ];
    }

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
