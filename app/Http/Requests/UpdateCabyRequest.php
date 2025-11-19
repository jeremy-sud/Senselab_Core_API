<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de validación para actualizar código CAByS
 *
 * @package App\Http\Requests
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class UpdateCabyRequest extends FormRequest
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
            'codigo' => ['sometimes', 'string', 'max:20', Rule::unique('cabys', 'codigo')->ignore($this->route('caby'))],
            'descripcion' => ['sometimes', 'string'],
            'impuesto_iva_predeterminado' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'activo' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo.unique' => 'Este código CAByS ya está registrado',
            'codigo.max' => 'El código CAByS no puede tener más de 20 caracteres',
            'impuesto_iva_predeterminado.numeric' => 'La tasa de IVA debe ser un número',
            'impuesto_iva_predeterminado.min' => 'La tasa de IVA debe ser mayor o igual a 0',
            'impuesto_iva_predeterminado.max' => 'La tasa de IVA no puede ser mayor a 100'
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
            'codigo' => 'código CAByS',
            'descripcion' => 'descripción',
            'impuesto_iva_predeterminado' => 'tasa de IVA predeterminada',
            'activo' => 'activo'
        ];
    }
}
