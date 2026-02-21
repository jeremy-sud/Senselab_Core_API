<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePresupuestoRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after:periodo_inicio'
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
            'periodo_inicio.required' => 'La fecha de inicio del período es obligatoria',
            'periodo_fin.required' => 'La fecha de fin del período es obligatoria',
            'periodo_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio'
        ];
    }
}
