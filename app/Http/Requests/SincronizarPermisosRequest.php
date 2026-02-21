<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SincronizarPermisosRequest extends FormRequest
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
            'rol_id' => 'required|integer|exists:roles,id',
            'permiso_ids' => 'required|array',
            'permiso_ids.*' => 'required|integer|exists:permisos,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rol_id.required' => 'El ID del rol es obligatorio.',
            'rol_id.exists' => 'El rol especificado no existe.',
            'permiso_ids.required' => 'Los permisos son obligatorios.',
            'permiso_ids.array' => 'Los permisos deben ser un arreglo.',
            'permiso_ids.*.exists' => 'Uno o más permisos especificados no existen.',
        ];
    }
}
