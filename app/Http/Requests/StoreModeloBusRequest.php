<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear Modelo de Bus
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreModeloBusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:modelos_buses,nombre']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del modelo es obligatorio',
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
