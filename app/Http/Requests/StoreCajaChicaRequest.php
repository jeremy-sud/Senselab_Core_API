<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCajaChicaRequest extends FormRequest
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
            'fecha' => 'required|date',
            'descripcion' => 'nullable|string',
            'monto' => 'required|numeric|min:0|max:99999999.99',
            'tipo' => 'required|string|in:Ingreso,Egreso',
            'responsable_id' => 'required|integer|exists:usuarios,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'fecha.required' => 'La fecha es requerida',
            'fecha.date' => 'La fecha debe ser una fecha válida',
            'monto.required' => 'El monto es requerido',
            'monto.numeric' => 'El monto debe ser un valor numérico',
            'monto.min' => 'El monto debe ser mayor o igual a 0',
            'monto.max' => 'El monto no puede superar 99,999,999.99',
            'tipo.required' => 'El tipo de movimiento es requerido',
            'tipo.in' => 'El tipo debe ser Ingreso o Egreso',
            'responsable_id.required' => 'El responsable es requerido',
            'responsable_id.exists' => 'El responsable especificado no existe',
        ];
    }
}
