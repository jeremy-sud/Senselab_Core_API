<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnidadMedidaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unidadId = $this->route('unidad_medida') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('unidades_medida', 'nombre')->ignore($unidadId)
            ],
            'abreviatura' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('unidades_medida', 'abreviatura')->ignore($unidadId)
            ],
            'codigo_oficial' => ['nullable', 'string', 'max:10'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe una unidad de medida con este nombre',
            'abreviatura.unique' => 'Ya existe una unidad de medida con esta abreviatura'
        ];
    }
}
