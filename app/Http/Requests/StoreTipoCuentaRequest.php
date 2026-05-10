<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Tipo de Cuenta Contable
 *
 * @package App\Http\Requests
 * @author Senselab - Jeremy Arias Solano
 */
class StoreTipoCuentaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100', 'unique:tipos_cuentas,nombre'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'naturaleza' => ['required', Rule::in(['Deudora', 'Acreedora'])],
            'activo' => ['nullable', 'boolean']
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
            'nombre.required' => 'El nombre del tipo de cuenta es obligatorio',
            'nombre.unique' => 'Este tipo de cuenta ya existe',
            'naturaleza.required' => 'La naturaleza de la cuenta es obligatoria',
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
