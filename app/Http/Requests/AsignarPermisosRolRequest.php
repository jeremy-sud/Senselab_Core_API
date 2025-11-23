<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarPermisosRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permisos' => 'required|array',
            'permisos.*' => 'required|integer|exists:permisos,id',
        ];
    }

    public function messages(): array
    {
        return [
            'permisos.required' => 'Debe especificar al menos un permiso.',
            'permisos.array' => 'Los permisos deben ser un arreglo.',
            'permisos.*.exists' => 'Uno o más permisos especificados no existen.',
        ];
    }
}
