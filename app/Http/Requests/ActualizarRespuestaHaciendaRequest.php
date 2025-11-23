<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarRespuestaHaciendaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'xml_respuesta_hacienda' => 'required|string',
            'estado_hacienda' => 'required|string|in:Aceptado,Rechazado,Procesando',
            'mensaje_hacienda' => 'nullable|string'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'xml_respuesta_hacienda.required' => 'El XML de respuesta de Hacienda es requerido',
            'xml_respuesta_hacienda.string' => 'El XML de respuesta debe ser texto',
            'estado_hacienda.required' => 'El estado de Hacienda es requerido',
            'estado_hacienda.in' => 'El estado debe ser Aceptado, Rechazado o Procesando',
            'mensaje_hacienda.string' => 'El mensaje de Hacienda debe ser texto',
        ];
    }
}
