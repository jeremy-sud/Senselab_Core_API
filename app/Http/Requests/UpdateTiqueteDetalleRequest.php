<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Tiquete
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateTiqueteDetalleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_pasajero' => ['nullable', 'string', 'max:255'],
            'identificacion_pasajero' => ['nullable', 'string', 'max:50'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Vendido', 'Usado', 'Cancelado'])],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre_pasajero' => 'nombre del pasajero',
            'identificacion_pasajero' => 'identificación del pasajero',
            'estado' => 'estado'
        ];
    }
}
