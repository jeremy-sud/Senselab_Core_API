<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:roles,nombre'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['sometimes', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['required', 'integer', 'exists:permisos,id']
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del rol es obligatorio',
            'nombre.unique' => 'Ya existe un rol con este nombre',
            'nombre.max' => 'El nombre no puede exceder 100 caracteres',
            'permisos.*.exists' => 'Uno o más permisos seleccionados no existen'
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'activo' => 'activo',
            'permisos' => 'permisos'
        ];
    }
}
