<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para crear Horario de Ruta
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreHorarioRutaRequest extends FormRequest
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
            'ruta_id' => ['required', 'integer', 'exists:rutas,id'],
            'bus_id' => ['required', 'integer', 'exists:buses_unidades,id'],
            'fecha_salida' => ['required', 'date'],
            'hora_salida' => ['required', 'date_format:H:i:s'],
            'fecha_llegada_estimada' => ['nullable', 'date', 'after_or_equal:fecha_salida'],
            'hora_llegada_estimada' => ['nullable', 'date_format:H:i:s'],
            'estado' => ['nullable', 'string', 'max:50', Rule::in(['Programado', 'Cancelado', 'En Viaje', 'Finalizado'])],
            'activo' => ['nullable', 'boolean']
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validar que el bus no esté asignado a otro viaje en el mismo horario
            if ($this->has('bus_id') && $this->has('fecha_salida') && $this->has('hora_salida')) {
                $conflicto = \App\Models\HorarioRuta::where('bus_id', $this->bus_id)
                    ->where('fecha_salida', $this->fecha_salida)
                    ->where('eliminado', 0)
                    ->whereIn('estado', ['Programado', 'En Viaje'])
                    ->exists();

                if ($conflicto) {
                    $validator->errors()->add('bus_id', 'Este bus ya tiene un viaje programado para esta fecha');
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ruta_id.required' => 'La ruta es obligatoria',
            'ruta_id.exists' => 'La ruta seleccionada no existe',
            'bus_id.required' => 'El bus es obligatorio',
            'bus_id.exists' => 'El bus seleccionado no existe',
            'fecha_salida.required' => 'La fecha de salida es obligatoria',
            'hora_salida.required' => 'La hora de salida es obligatoria',
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
