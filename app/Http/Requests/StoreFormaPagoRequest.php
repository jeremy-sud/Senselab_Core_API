<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormaPagoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255', 'unique:formas_pago,nombre'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'requiere_referencia' => ['sometimes', 'boolean'],
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
            'nombre.required' => 'El nombre de la forma de pago es obligatorio',
            'nombre.unique' => 'Ya existe una forma de pago con este nombre',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres'
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
            'tipo' => 'tipo',
            'requiere_referencia' => 'requiere referencia',
            'activo' => 'activo'
        ];
    }
}
