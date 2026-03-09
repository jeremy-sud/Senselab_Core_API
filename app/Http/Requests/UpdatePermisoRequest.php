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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $permisoId = $this->route('permiso') ?? $this->route('id');

        return [
            'nombre' => ['sometimes', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'modulo' => ['nullable', 'string', 'max:50'],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('permisos', 'slug')->ignore($permisoId)
            ],
            'activo' => ['sometimes', 'boolean']
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
            'slug.unique' => 'Ya existe un permiso con este slug'
        ];
    }
}
