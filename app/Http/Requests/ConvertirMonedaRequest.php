<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertirMonedaRequest extends FormRequest
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
            'monto' => 'required|numeric|min:0',
            'moneda_origen' => 'required|string|size:3',
            'moneda_destino' => 'required|string|size:3',
            'fecha' => 'nullable|date',
            'usar_tasa' => 'nullable|in:compra,venta',
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
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.min' => 'El monto no puede ser negativo.',
            'moneda_origen.required' => 'La moneda de origen es obligatoria.',
            'moneda_origen.size' => 'La moneda de origen debe tener 3 caracteres (código ISO).',
            'moneda_destino.required' => 'La moneda de destino es obligatoria.',
            'moneda_destino.size' => 'La moneda de destino debe tener 3 caracteres (código ISO).',
            'fecha.date' => 'La fecha debe tener un formato válido.',
            'usar_tasa.in' => 'El tipo de tasa debe ser "compra" o "venta".',
        ];
    }
}
