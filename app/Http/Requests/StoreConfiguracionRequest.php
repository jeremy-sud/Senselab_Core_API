<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => [
                'required',
                'string',
                'max:255',
                Rule::unique('configuraciones')->where(function ($query) {
                    return $query->where('empresa_id', auth()->user()->empresa_id);
                })
            ],
            'valor' => 'required|string',
            'tipo_dato' => 'required|string|in:texto,numero,booleano,json',
            'descripcion' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es obligatoria',
            'clave.unique' => 'Ya existe una configuración con esta clave en tu empresa',
            'valor.required' => 'El valor es obligatorio',
            'tipo_dato.required' => 'El tipo de dato es obligatorio',
            'tipo_dato.in' => 'El tipo de dato debe ser: texto, numero, booleano o json'
        ];
    }
}
