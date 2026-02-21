<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCuentaBancariaRequest extends FormRequest
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
            'empresa_id' => ['required', 'exists:empresas,id'],
            'banco' => ['required', 'string', 'max:100'],
            'numero_cuenta' => ['required', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'size:22', 'regex:/^CR\d{20}$/', 'unique:cuentas_bancarias,iban'],
            'tipo_cuenta' => ['required', 'in:corriente,ahorros,cliente,colones,dolares'],
            'moneda' => ['required', 'in:CRC,USD,EUR'],
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

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_id.required' => 'La empresa es obligatoria',
            'banco.required' => 'El nombre del banco es obligatorio',
            'numero_cuenta.required' => 'El número de cuenta es obligatorio',
            'iban.regex' => 'IBAN debe iniciar con CR seguido de 20 dígitos',
            'iban.size' => 'IBAN debe tener 22 caracteres',
            'iban.unique' => 'Este IBAN ya está registrado',
            'tipo_cuenta.required' => 'El tipo de cuenta es obligatorio',
            'moneda.required' => 'La moneda es obligatoria',
        ];
    }
}
