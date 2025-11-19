<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Bus/Unidad de Transporte
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateBusUnidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa' => ['sometimes', 'string', 'max:20', Rule::unique('buses_unidades', 'placa')->ignore($this->route('bus_unidad'))],
            'modelo_id' => ['nullable', 'integer', 'exists:modelos_buses,id'],
            'capacidad_asientos' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'identificador_interno' => ['nullable', 'string', 'max:50'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'placa.unique' => 'Esta placa ya está registrada',
            'modelo_id.exists' => 'El modelo seleccionado no existe',
            'capacidad_asientos.min' => 'La capacidad debe ser al menos 1 asiento',
            'capacidad_asientos.max' => 'La capacidad máxima es de 100 asientos'
        ];
    }

    public function attributes(): array
    {
        return [
            'placa' => 'placa',
            'modelo_id' => 'modelo',
            'capacidad_asientos' => 'capacidad de asientos',
            'identificador_interno' => 'identificador interno'
        ];
    }
}
