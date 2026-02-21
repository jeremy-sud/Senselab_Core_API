<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovimientoCajaChicaRequest extends FormRequest
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
            'caja_chica_id' => 'required|integer|exists:caja_chica,id',
            'fecha_movimiento' => 'required|date',
            'tipo_movimiento' => 'required|string|in:Ingreso,Egreso,Reembolso,Ajuste',
            'monto' => 'required|numeric|min:0|max:99999999.99',
            'numero_comprobante' => 'nullable|string|max:100',
            'concepto' => 'required|string',
            'cuenta_contable_id' => 'nullable|integer|exists:cuentas_contables,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'caja_chica_id.required' => 'La caja chica es requerida',
            'caja_chica_id.exists' => 'La caja chica especificada no existe',
            'fecha_movimiento.required' => 'La fecha del movimiento es requerida',
            'fecha_movimiento.date' => 'La fecha del movimiento debe ser una fecha válida',
            'tipo_movimiento.required' => 'El tipo de movimiento es requerido',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser Ingreso, Egreso, Reembolso o Ajuste',
            'monto.required' => 'El monto es requerido',
            'monto.numeric' => 'El monto debe ser un valor numérico',
            'monto.min' => 'El monto debe ser mayor o igual a 0',
            'monto.max' => 'El monto no puede superar 99,999,999.99',
            'numero_comprobante.max' => 'El número de comprobante no puede exceder 100 caracteres',
            'concepto.required' => 'El concepto es requerido',
            'cuenta_contable_id.exists' => 'La cuenta contable especificada no existe',
        ];
    }
}
