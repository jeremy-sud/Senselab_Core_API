<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Tipo de Cuenta Contable
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateTipoCuentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('tipos_cuentas', 'nombre')->ignore($this->route('tipo_cuenta'))],
            'descripcion' => ['nullable', 'string'],
            'naturaleza' => ['sometimes', Rule::in(['Deudora', 'Acreedora'])],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Este tipo de cuenta ya existe',
            'naturaleza.in' => 'La naturaleza debe ser Deudora o Acreedora'
        ];
    }

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
