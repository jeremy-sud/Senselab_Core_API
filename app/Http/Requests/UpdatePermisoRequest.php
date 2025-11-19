<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permisoId = $this->route('permiso') ?? $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'modulo' => ['nullable', 'string', 'max:50'],
            'codigo_unico' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('permisos', 'codigo_unico')->ignore($permisoId)
            ],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            'codigo_unico.unique' => 'Ya existe un permiso con este código'
        ];
    }
}
