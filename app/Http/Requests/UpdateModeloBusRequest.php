<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Modelo de Bus
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateModeloBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('modelos_buses', 'nombre')->ignore($this->route('modelo_bus'))]
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Este modelo ya existe'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del modelo'
        ];
    }
}
