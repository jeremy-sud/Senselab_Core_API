<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
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
            'clave' => 'sometimes|required|string|max:255',
            'valor' => 'sometimes|required|string',
            'tipo_dato' => 'sometimes|required|string|in:string,integer,float,boolean,json,array',
            'descripcion' => 'nullable|string'
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
            'clave.required' => 'La clave es obligatoria',
            'valor.required' => 'El valor es obligatorio',
            'tipo_dato.in' => 'El tipo de dato debe ser: string, integer, float, boolean, json o array'
        ];
    }
}
