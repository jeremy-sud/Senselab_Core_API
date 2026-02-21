<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePresupuestoRequest extends FormRequest
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
            'nombre' => 'sometimes|required|string|max:255',
            'periodo_inicio' => 'sometimes|required|date',
            'periodo_fin' => 'sometimes|required|date|after:periodo_inicio'
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
            'nombre.required' => 'El nombre del presupuesto es obligatorio',
            'periodo_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
            'periodo_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio'
        ];
    }
}
