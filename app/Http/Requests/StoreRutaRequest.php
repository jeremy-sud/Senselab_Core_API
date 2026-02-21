<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear Ruta
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class StoreRutaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'distancia_km' => ['nullable', 'numeric', 'min:0'],
            'duracion_estimada' => ['nullable', 'integer', 'min:1'],
            'tarifa_base' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
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
            'nombre.required' => 'El nombre de la ruta es obligatorio',
            'origen.required' => 'El origen es obligatorio',
            'destino.required' => 'El destino es obligatorio',
            'tarifa_base.required' => 'La tarifa base es obligatoria',
            'tarifa_base.min' => 'La tarifa debe ser mayor o igual a 0',
            'duracion_estimada.min' => 'La duración debe ser al menos 1 minuto'
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
            'origen' => 'origen',
            'destino' => 'destino',
            'distancia_km' => 'distancia en km',
            'duracion_estimada' => 'duración estimada',
            'tarifa_base' => 'tarifa base',
            'observaciones' => 'observaciones'
        ];
    }
}
