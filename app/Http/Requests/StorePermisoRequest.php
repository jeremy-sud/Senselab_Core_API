<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'modulo' => ['nullable', 'string', 'max:50'],
            'codigo_unico' => ['required', 'string', 'max:100', 'unique:permisos,codigo_unico'],
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
            'nombre.required' => 'El nombre del permiso es obligatorio',
            'codigo_unico.required' => 'El código único es obligatorio',
            'codigo_unico.unique' => 'Ya existe un permiso con este código',
            'codigo_unico.max' => 'El código único no puede exceder 100 caracteres'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'modulo' => 'módulo',
            'codigo_unico' => 'código único',
            'activo' => 'activo'
        ];
    }
}
