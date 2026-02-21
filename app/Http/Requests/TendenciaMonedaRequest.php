<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TendenciaMonedaRequest extends FormRequest
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'dias' => 'nullable|integer|min:7|max:365',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'moneda_origen.required' => 'La moneda de origen es requerida',
            'moneda_origen.size' => 'La moneda de origen debe tener exactamente 3 caracteres',
            'moneda_destino.required' => 'La moneda de destino es requerida',
            'moneda_destino.size' => 'La moneda de destino debe tener exactamente 3 caracteres',
            'dias.integer' => 'Los días deben ser un número entero',
            'dias.min' => 'El período mínimo es de 7 días',
            'dias.max' => 'El período máximo es de 365 días',
        ];
    }
}
