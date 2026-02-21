<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetallePresupuestoRequest extends FormRequest
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
            'monto_presupuestado' => 'required|numeric|min:0'
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
            'monto_presupuestado.required' => 'El monto presupuestado es obligatorio',
            'monto_presupuestado.numeric' => 'El monto presupuestado debe ser un número',
            'monto_presupuestado.min' => 'El monto presupuestado debe ser mayor o igual a 0'
        ];
    }
}
