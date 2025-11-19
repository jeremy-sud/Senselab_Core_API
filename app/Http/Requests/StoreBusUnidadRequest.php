<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear Bus/Unidad de Transporte
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreBusUnidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'placa' => ['required', 'string', 'max:20', 'unique:buses_unidades,placa'],
            'modelo_id' => ['nullable', 'integer', 'exists:modelos_buses,id'],
            'capacidad_asientos' => ['required', 'integer', 'min:1', 'max:100'],
            'identificador_interno' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'placa.required' => 'La placa es obligatoria',
            'placa.unique' => 'Esta placa ya está registrada',
            'modelo_id.exists' => 'El modelo seleccionado no existe',
            'capacidad_asientos.required' => 'La capacidad de asientos es obligatoria',
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
