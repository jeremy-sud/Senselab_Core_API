<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clave' => 'sometimes|required|string|max:255',
            'valor' => 'sometimes|required|string',
            'tipo_dato' => 'sometimes|required|string|in:texto,numero,booleano,json',
            'descripcion' => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'clave.required' => 'La clave es obligatoria',
            'valor.required' => 'El valor es obligatorio',
            'tipo_dato.in' => 'El tipo de dato debe ser: texto, numero, booleano o json'
        ];
    }
}
