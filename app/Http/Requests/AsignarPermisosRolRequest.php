<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AsignarPermisosRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'permisos' => 'required|array',
            'permisos.*' => 'required|integer|exists:permisos,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permisos.required' => 'Debe especificar al menos un permiso.',
            'permisos.array' => 'Los permisos deben ser un arreglo.',
            'permisos.*.exists' => 'Uno o más permisos especificados no existen.',
        ];
    }
}
