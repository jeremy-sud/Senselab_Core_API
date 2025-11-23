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
        return false;
    }

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
            'fecha_movimiento' => ['sometimes', 'date'],
            'fecha_valor' => ['nullable', 'date'],
            'tipo_movimiento' => ['sometimes', 'in:deposito,retiro,transferencia_entrada,transferencia_salida,comision,interes,ajuste'],
            'numero_referencia' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['sometimes', 'string', 'max:255'],
            'monto' => ['sometimes', 'numeric', 'not_in:0'],
            'saldo_despues' => ['nullable', 'numeric'],
            'beneficiario' => ['nullable', 'string', 'max:200'],
            'conciliado' => ['boolean'],
            'fecha_conciliacion' => ['nullable', 'date'],
            'asiento_contable_id' => ['nullable', 'exists:asientos_contables,id'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
