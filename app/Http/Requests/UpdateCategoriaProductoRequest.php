<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoriaId = $this->route('categoria_producto') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categorias_productos', 'nombre')->where(function ($query) use ($categoriaId) {
                    return $query->where('empresa_id', auth()->user()->empresa_id)
                                 ->where('eliminado', 0)
                                 ->where('id', '!=', $categoriaId);
                })
            ],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una categoría con este nombre en la empresa'
        ];
    }
}
