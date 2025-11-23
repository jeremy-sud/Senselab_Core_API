<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCodigoActividadEconomicaRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:10', 'unique:codigos_actividad_economica,codigo'],
            'descripcion' => ['required', 'string', 'max:255'],
            'categoria_principal' => ['nullable', 'string', 'max:100'],
            'activo' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya existe',
            'descripcion.required' => 'La descripción es obligatoria',
        ];
    }
}
