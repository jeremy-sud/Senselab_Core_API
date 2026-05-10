<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request de validación para crear Tipo de Impuesto
 *
 * @package App\Http\Requests
 * @author Senselab - Jeremy Arias Solano
 */
class StoreTipoImpuestoRequest extends FormRequest
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
            'codigo_hacienda' => ['required', 'string', 'max:10', 'unique:tipos_impuesto,codigo_hacienda'],
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'comentario' => ['nullable', 'string', 'max:1000'],
            'activo' => ['nullable', 'boolean']
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
            'codigo_hacienda.required' => 'El código de Hacienda es obligatorio',
            'codigo_hacienda.unique' => 'Este código de Hacienda ya está registrado',
            'codigo_hacienda.max' => 'El código de Hacienda no puede tener más de 10 caracteres',
            'nombre.required' => 'El nombre es obligatorio',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'codigo_hacienda' => 'código de Hacienda',
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'comentario' => 'comentario',
            'activo' => 'activo'
        ];
    }
}
