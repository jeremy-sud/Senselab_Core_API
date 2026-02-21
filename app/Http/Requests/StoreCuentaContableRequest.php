<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Cuenta Contable
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreCuentaContableRequest extends FormRequest
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
        $empresaId = $this->user()->empresa_id;

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cuentas_contables', 'codigo')->where(function ($query) use ($empresaId) {
                    return $query->where('empresa_id', $empresaId)->where('eliminado', 0);
                })
            ],
            'tipo_cuenta_id' => ['nullable', 'integer', 'exists:tipos_cuentas,id'],
            'cuenta_padre_id' => ['nullable', 'integer', 'exists:cuentas_contables,id'],
            'permite_movimientos' => ['nullable', 'boolean'],
            'saldo_actual' => ['nullable', 'numeric'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
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
            // Validar que la cuenta padre no sea la misma cuenta
            if ($this->filled('cuenta_padre_id') && $this->cuenta_padre_id == $this->route('cuenta_contable')) {
                $validator->errors()->add('cuenta_padre_id', 'Una cuenta no puede ser padre de sí misma');
            }
        });
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
            'nombre.required' => 'El nombre de la cuenta es obligatorio',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres',
            'codigo.required' => 'El código de la cuenta es obligatorio',
            'codigo.unique' => 'Este código de cuenta ya está registrado en su empresa',
            'codigo.max' => 'El código no puede tener más de 50 caracteres',
            'tipo_cuenta_id.exists' => 'El tipo de cuenta seleccionado no existe',
            'cuenta_padre_id.exists' => 'La cuenta padre seleccionada no existe'
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
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'codigo' => 'código',
            'tipo_cuenta_id' => 'tipo de cuenta',
            'cuenta_padre_id' => 'cuenta padre',
            'permite_movimientos' => 'permite movimientos',
            'saldo_actual' => 'saldo actual',
            'activo' => 'activo'
        ];
    }
}
