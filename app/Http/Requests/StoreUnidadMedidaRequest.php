<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:unidades_medida,nombre'],
            'abreviatura' => ['required', 'string', 'max:20', 'unique:unidades_medida,abreviatura'],
            'codigo_oficial' => ['nullable', 'string', 'max:10'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la unidad de medida es obligatorio',
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre',
            'abreviatura.required' => 'La abreviatura es obligatoria',
            'abreviatura.unique' => 'Ya existe una unidad de medida con esta abreviatura',
            'abreviatura.max' => 'La abreviatura no puede exceder 20 caracteres'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'abreviatura' => 'abreviatura',
            'codigo_oficial' => 'código oficial',
            'activo' => 'activo'
        ];
    }
}
