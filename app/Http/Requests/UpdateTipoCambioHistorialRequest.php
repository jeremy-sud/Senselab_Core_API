<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoCambioHistorialRequest extends FormRequest
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
            'fecha' => 'sometimes|date',
            'moneda_origen' => 'sometimes|string|size:3',
            'moneda_destino' => 'sometimes|string|size:3',
            'tasa_compra' => 'sometimes|numeric|min:0|max:999999.99999',
            'tasa_venta' => 'sometimes|numeric|min:0|max:999999.99999',
            'fuente' => 'sometimes|nullable|string|max:50',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha.date' => 'La fecha debe ser una fecha válida',
            'moneda_origen.size' => 'La moneda de origen debe tener exactamente 3 caracteres (ej: USD)',
            'moneda_destino.size' => 'La moneda de destino debe tener exactamente 3 caracteres (ej: CRC)',
            'tasa_compra.numeric' => 'La tasa de compra debe ser un valor numérico',
            'tasa_compra.min' => 'La tasa de compra debe ser mayor o igual a 0',
            'tasa_venta.numeric' => 'La tasa de venta debe ser un valor numérico',
            'tasa_venta.min' => 'La tasa de venta debe ser mayor o igual a 0',
            'fuente.max' => 'La fuente no puede superar los 50 caracteres',
        ];
    }
}
