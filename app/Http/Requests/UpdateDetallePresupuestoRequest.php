<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetallePresupuestoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_presupuestado' => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'monto_presupuestado.required' => 'El monto presupuestado es obligatorio',
            'monto_presupuestado.numeric' => 'El monto presupuestado debe ser un número',
            'monto_presupuestado.min' => 'El monto presupuestado debe ser mayor o igual a 0'
        ];
    }
}
