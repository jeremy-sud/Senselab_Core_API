<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetalleEntradaInventarioRequest extends FormRequest
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
            'cantidad' => 'required|numeric|min:0.01',
            'costo_unitario' => 'required|numeric|min:0',
            'lote' => 'nullable|string|max:50',
            'fecha_vencimiento' => 'nullable|date|after:today'
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
            'cantidad.required' => 'La cantidad es obligatoria',
            'cantidad.numeric' => 'La cantidad debe ser un número',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'costo_unitario.required' => 'El costo unitario es obligatorio',
            'costo_unitario.numeric' => 'El costo unitario debe ser un número',
            'costo_unitario.min' => 'El costo unitario debe ser mayor o igual a 0',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a hoy'
        ];
    }
}
