<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoCuentaCobrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuenta_por_cobrar_id' => 'required|integer|exists:cuentas_por_cobrar,id',
            'forma_pago_id' => 'required|integer|exists:formas_pago,id',
            'fecha_pago' => 'required|date',
            'monto_pago' => 'required|numeric|min:0|max:99999999.99',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'nullable|string|size:3',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'cuenta_por_cobrar_id.required' => 'La cuenta por cobrar es requerida',
            'cuenta_por_cobrar_id.exists' => 'La cuenta por cobrar no existe',
            'forma_pago_id.required' => 'La forma de pago es requerida',
            'forma_pago_id.exists' => 'La forma de pago no existe',
            'fecha_pago.required' => 'La fecha de pago es requerida',
            'fecha_pago.date' => 'La fecha de pago debe ser válida',
            'monto_pago.required' => 'El monto es requerido',
            'monto_pago.numeric' => 'El monto debe ser numérico',
            'monto_pago.min' => 'El monto debe ser mayor a 0',
            'moneda.size' => 'La moneda debe tener 3 caracteres',
        ];
    }
}
