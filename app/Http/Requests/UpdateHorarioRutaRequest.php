<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar Horario de Ruta
 *
 * @package App\Http\Requests
 * @author Senselab - Jeremy Arias Solano
 */
class UpdateHorarioRutaRequest extends FormRequest
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
            'ruta_id' => ['sometimes', 'integer', 'exists:rutas,id'],
            'bus_id' => ['sometimes', 'integer', 'exists:buses_unidades,id'],
            'fecha_salida' => ['sometimes', 'date'],
            'hora_salida' => ['sometimes', 'date_format:H:i:s'],
            'fecha_llegada_estimada' => ['nullable', 'date', 'after_or_equal:fecha_salida'],
            'hora_llegada_estimada' => ['nullable', 'date_format:H:i:s'],
            'estado' => ['sometimes', 'string', 'max:50', Rule::in(['Programado', 'Cancelado', 'En Viaje', 'Finalizado'])],
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
            'ruta_id.exists' => 'La ruta seleccionada no existe',
            'bus_id.exists' => 'El bus seleccionado no existe',
            'fecha_llegada_estimada.after_or_equal' => 'La fecha de llegada debe ser igual o posterior a la fecha de salida'
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
            'ruta_id' => 'ruta',
            'bus_id' => 'bus',
            'fecha_salida' => 'fecha de salida',
            'hora_salida' => 'hora de salida',
            'fecha_llegada_estimada' => 'fecha de llegada estimada',
            'hora_llegada_estimada' => 'hora de llegada estimada',
            'estado' => 'estado'
        ];
    }
}
