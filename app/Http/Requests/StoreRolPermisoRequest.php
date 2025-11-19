<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolPermisoRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'rol_id' => 'required|integer|exists:roles,id',
            'permiso_id' => 'required|integer|exists:permisos,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rol_id.required' => 'El rol es requerido',
            'rol_id.exists' => 'El rol especificado no existe',
            'permiso_id.required' => 'El permiso es requerido',
            'permiso_id.exists' => 'El permiso especificado no existe',
        ];
    }
}
