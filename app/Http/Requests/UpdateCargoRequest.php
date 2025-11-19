<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cargoId = $this->route('cargo') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('cargos', 'nombre')->ignore($cargoId)
            ],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un cargo con este nombre'
        ];
    }
}
