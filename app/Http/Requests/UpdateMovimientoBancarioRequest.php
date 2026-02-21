<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoBancarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cuenta_bancaria_id' => ['sometimes', 'exists:cuentas_bancarias,id'],
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'fecha_movimiento' => ['sometimes', 'date', 'before_or_equal:today'],
            'fecha_valor' => ['nullable', 'date'],
            'tipo_movimiento' => ['sometimes', 'in:deposito,retiro,transferencia_entrada,transferencia_salida,comision,interes,ajuste'],
            'numero_referencia' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['sometimes', 'string', 'max:255'],
            'monto' => ['sometimes', 'numeric', 'not_in:0', 'min:0.01'],
            'saldo_despues' => ['nullable', 'numeric'],
            'beneficiario' => ['nullable', 'string', 'max:200'],
            'conciliado' => ['boolean'],
            'fecha_conciliacion' => ['nullable', 'date', 'required_if:conciliado,true'],
            'asiento_contable_id' => ['nullable', 'exists:asientos_contables,id'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no existe',
            'empresa_id.exists' => 'La empresa seleccionada no existe',
            'fecha_movimiento.date' => 'La fecha del movimiento debe ser una fecha válida',
            'fecha_movimiento.before_or_equal' => 'La fecha del movimiento no puede ser futura',
            'fecha_valor.date' => 'La fecha valor debe ser una fecha válida',
            'tipo_movimiento.in' => 'El tipo de movimiento no es válido',
            'numero_referencia.max' => 'El número de referencia no puede exceder 50 caracteres',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres',
            'monto.not_in' => 'El monto no puede ser cero',
            'monto.min' => 'El monto debe ser mayor a cero',
            'beneficiario.max' => 'El nombre del beneficiario no puede exceder 200 caracteres',
            'fecha_conciliacion.date' => 'La fecha de conciliación debe ser una fecha válida',
            'fecha_conciliacion.required_if' => 'La fecha de conciliación es obligatoria si el movimiento está conciliado',
            'asiento_contable_id.exists' => 'El asiento contable seleccionado no existe',
            'notas.max' => 'Las notas no pueden exceder 1000 caracteres',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'cuenta_bancaria_id' => 'cuenta bancaria',
            'fecha_movimiento' => 'fecha del movimiento',
            'fecha_valor' => 'fecha valor',
            'tipo_movimiento' => 'tipo de movimiento',
            'numero_referencia' => 'número de referencia',
            'asiento_contable_id' => 'asiento contable',
            'fecha_conciliacion' => 'fecha de conciliación',
        ];
    }

    /**
     * Configure the validator instance.
     */
    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que si está conciliado, no se puede desconciliar sin autorización
            if ($this->has('conciliado') && $this->conciliado === false) {
                $movimiento = \App\Models\MovimientoBancario::find($this->route('movimiento_bancario'));
                if ($movimiento && $movimiento->conciliado) {
                    $validator->errors()->add(
                        'conciliado',
                        'No se puede desconciliar un movimiento que ya fue conciliado. Contacte al administrador.'
                    );
                }
            }

            // Validar que la fecha de conciliación no sea anterior a la fecha del movimiento
            if ($this->fecha_conciliacion && $this->fecha_movimiento) {
                if (strtotime($this->fecha_conciliacion) < strtotime($this->fecha_movimiento)) {
                    $validator->errors()->add(
                        'fecha_conciliacion',
                        'La fecha de conciliación no puede ser anterior a la fecha del movimiento'
                    );
                }
            }
        });
    }
}
