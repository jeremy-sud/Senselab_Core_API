<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:marcas,nombre'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la marca es obligatorio',
            'nombre.unique' => 'Ya existe una marca con este nombre',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'activo' => 'activo'
        ];
    }
}
