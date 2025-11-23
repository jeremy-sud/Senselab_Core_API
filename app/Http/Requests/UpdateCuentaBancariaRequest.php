<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCuentaBancariaRequest extends FormRequest
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
        $id = $this->route('cuenta_bancaria');
        
        return [
            'empresa_id' => ['sometimes', 'exists:empresas,id'],
            'banco' => ['sometimes', 'string', 'max:100'],
            'numero_cuenta' => ['sometimes', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'size:22', 'regex:/^CR\d{20}$/', 'unique:cuentas_bancarias,iban,' . $id],
            'tipo_cuenta' => ['sometimes', 'in:corriente,ahorros,cliente,colones,dolares'],
            'moneda' => ['sometimes', 'in:CRC,USD,EUR'],
            'saldo_actual' => ['nullable', 'numeric'],
            'cuenta_contable_id' => ['nullable', 'exists:cuentas_contables,id'],
            'sucursal_banco' => ['nullable', 'string', 'max:100'],
            'contacto_ejecutivo' => ['nullable', 'string', 'max:100'],
            'telefono_ejecutivo' => ['nullable', 'string', 'max:20'],
            'activa' => ['boolean'],
            'es_principal' => ['boolean'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
