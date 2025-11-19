<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categorias_productos', 'nombre')->where(function ($query) {
                    return $query->where('empresa_id', auth()->user()->empresa_id)
                                 ->where('eliminado', 0);
                })
            ],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio',
            'nombre.unique' => 'Ya existe una categoría con este nombre en la empresa',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres'
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
