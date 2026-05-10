<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Tipo de Cuenta Contable
 *
 * @package App\Http\Requests
 * @author Senselab - Jeremy Arias Solano
 */
class UpdateTipoCuentaRequest extends FormRequest
{
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
        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('tipos_cuentas', 'nombre')->ignore($this->route('tipo_cuenta'))],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'naturaleza' => ['sometimes', Rule::in(['Deudora', 'Acreedora'])],
            'activo' => ['sometimes', 'boolean']
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
            'nombre.unique' => 'Este tipo de cuenta ya existe',
            'naturaleza.in' => 'La naturaleza debe ser Deudora o Acreedora'
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
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'naturaleza' => 'naturaleza',
            'activo' => 'activo'
        ];
    }
}
