<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoCuentaCobrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cuenta_por_cobrar_id' => 'sometimes|integer|exists:cuentas_por_cobrar,id',
            'forma_pago_id' => 'sometimes|integer|exists:formas_pago,id',
            'fecha_pago' => 'sometimes|date',
            'monto_pago' => 'sometimes|numeric|min:0|max:99999999.99',
            'numero_referencia' => 'nullable|string|max:100',
            'moneda' => 'sometimes|string|size:3',
            'observaciones' => 'nullable|string',
        ];
    }
}
