<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarRolesUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario_id' => 'required|exists:usuarios,id',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.exists' => 'El usuario especificado no existe.',
            'roles.required' => 'Debe especificar al menos un rol.',
            'roles.array' => 'Los roles deben ser un arreglo.',
            'roles.*.exists' => 'Uno o más roles especificados no existen.',
        ];
    }
}
