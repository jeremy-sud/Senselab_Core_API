<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarcaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $marcaId = $this->route('marca') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('marcas', 'nombre')->ignore($marcaId)
            ],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una marca con este nombre'
        ];
    }
}
