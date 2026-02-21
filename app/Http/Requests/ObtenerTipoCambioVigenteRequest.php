<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ObtenerTipoCambioVigenteRequest extends FormRequest
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
            'fecha' => 'nullable|date',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
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
            'fecha.date' => 'La fecha debe tener un formato válido.',
            'moneda_origen.required' => 'La moneda de origen es obligatoria.',
            'moneda_origen.size' => 'La moneda de origen debe tener 3 caracteres (código ISO).',
            'moneda_destino.required' => 'La moneda de destino es obligatoria.',
            'moneda_destino.size' => 'La moneda de destino debe tener 3 caracteres (código ISO).',
        ];
    }
}
