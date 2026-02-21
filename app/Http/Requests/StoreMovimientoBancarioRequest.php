<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoBancarioRequest extends FormRequest
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
            'cuenta_bancaria_id' => ['required', 'exists:cuentas_bancarias,id'],
            'empresa_id' => ['required', 'exists:empresas,id'],
            'fecha_movimiento' => ['required', 'date'],
            'fecha_valor' => ['nullable', 'date'],
            'tipo_movimiento' => ['required', 'in:deposito,retiro,transferencia_entrada,transferencia_salida,comision,interes,ajuste'],
            'numero_referencia' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'not_in:0'],
            'saldo_despues' => ['nullable', 'numeric'],
            'beneficiario' => ['nullable', 'string', 'max:200'],
            'conciliado' => ['boolean'],
            'fecha_conciliacion' => ['nullable', 'date', 'required_if:conciliado,true'],
            'asiento_contable_id' => ['nullable', 'exists:asientos_contables,id'],
            'notas' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuenta_bancaria_id.required' => 'La cuenta bancaria es obligatoria',
            'empresa_id.required' => 'La empresa es obligatoria',
            'fecha_movimiento.required' => 'La fecha del movimiento es obligatoria',
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio',
            'descripcion.required' => 'La descripción es obligatoria',
            'monto.required' => 'El monto es obligatorio',
            'monto.not_in' => 'El monto no puede ser cero',
        ];
    }
}
