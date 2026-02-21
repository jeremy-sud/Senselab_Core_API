<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolPermisoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rol_id' => 'sometimes|integer|exists:roles,id',
            'permiso_id' => 'sometimes|integer|exists:permisos,id',
            'activo' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rol_id.exists' => 'El rol especificado no existe',
            'permiso_id.exists' => 'El permiso especificado no existe',
            'activo.boolean' => 'El campo activo debe ser verdadero o falso',
        ];
    }
}
