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
        return true;
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
            'telefono_ejecutivo' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-\+\(\)\s]+$/'],
            'activa' => ['boolean'],
            'es_principal' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_id.exists' => 'La empresa seleccionada no existe',
            'banco.max' => 'El nombre del banco no puede exceder 100 caracteres',
            'numero_cuenta.max' => 'El número de cuenta no puede exceder 50 caracteres',
            'iban.size' => 'El IBAN debe tener exactamente 22 caracteres',
            'iban.regex' => 'El IBAN debe comenzar con CR seguido de 20 dígitos (ej: CR12010200009999999999)',
            'iban.unique' => 'Este IBAN ya está registrado en otra cuenta',
            'tipo_cuenta.in' => 'El tipo de cuenta no es válido',
            'moneda.in' => 'La moneda debe ser CRC, USD o EUR',
            'cuenta_contable_id.exists' => 'La cuenta contable seleccionada no existe',
            'telefono_ejecutivo.regex' => 'El teléfono no tiene un formato válido',
            'telefono_ejecutivo.max' => 'El teléfono no puede exceder 20 caracteres',
            'notas.max' => 'Las notas no pueden exceder 1000 caracteres',
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
            'empresa_id' => 'empresa',
            'numero_cuenta' => 'número de cuenta',
            'tipo_cuenta' => 'tipo de cuenta',
            'cuenta_contable_id' => 'cuenta contable',
            'sucursal_banco' => 'sucursal del banco',
            'contacto_ejecutivo' => 'ejecutivo de cuenta',
            'telefono_ejecutivo' => 'teléfono del ejecutivo',
            'es_principal' => 'cuenta principal',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validar que solo haya una cuenta principal por empresa y moneda
            if ($this->has('es_principal') && $this->es_principal === true) {
                $empresaId = $this->empresa_id ?? auth()->user()->empresa_id;
                $moneda = $this->moneda ?? null;
                $cuentaId = $this->route('cuenta_bancaria');

                $query = \App\Models\CuentaBancaria::where('empresa_id', $empresaId)
                    ->where('es_principal', true)
                    ->where('id', '!=', $cuentaId);

                if ($moneda) {
                    $query->where('moneda', $moneda);
                }

                if ($query->exists()) {
                    $validator->errors()->add(
                        'es_principal',
                        'Ya existe una cuenta principal para esta empresa' . ($moneda ? " en moneda $moneda" : '')
                    );
                }
            }

            // Validar que stock_maximo >= stock_minimo no aplica aquí, era del otro archivo
        });
    }
}
