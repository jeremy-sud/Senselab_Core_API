<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rolId = $this->route('rol') ?? $this->route('id');

        return [
            'nombre' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('roles', 'nombre')->ignore($rolId)
            ],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['required', 'integer', 'exists:permisos,id']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'permisos.*.exists' => 'Uno o más permisos seleccionados no existen'
        ];
    }
}
