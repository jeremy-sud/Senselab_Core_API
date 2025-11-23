<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TasaImpuestoVigenteRequest extends FormRequest
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
            'tipo_impuesto_id' => 'required|integer|exists:tipos_impuesto,id',
            'fecha' => 'nullable|date'
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
            'tipo_impuesto_id.required' => 'El tipo de impuesto es requerido',
            'tipo_impuesto_id.integer' => 'El tipo de impuesto debe ser un número entero',
            'tipo_impuesto_id.exists' => 'El tipo de impuesto especificado no existe',
            'fecha.date' => 'La fecha debe ser una fecha válida',
        ];
    }
}
