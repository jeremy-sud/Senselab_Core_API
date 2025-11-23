<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'roles' => 'required|array',
            'roles.*' => 'required|integer|exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'Debe especificar al menos un rol.',
            'roles.array' => 'Los roles deben ser un arreglo.',
            'roles.*.exists' => 'Uno o más roles especificados no existen.',
        ];
    }
}
